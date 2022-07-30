<?php

namespace App\MealProviders;

use App\MealInfo;
use App\MealProviders\Interfaces\HasWeeklyOrders;
use App\Order;
use DiDom\Document;
use DiDom\Element;
use DiDom\Exceptions\InvalidSelectorException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Ixudra\Curl\Facades\Curl;
use Symfony\Component\HttpFoundation\Response;

class Holzke extends AbstractMealProvider implements HasWeeklyOrders
{

    private string $cookieJar = '';

    private bool $isLoggedIn = false;

    public function __construct()
    {
        $this->cookieJar = storage_path('holzke_cookie.txt');

        $this->login();
    }

    private function login(): void
    {
        $response = Curl::to('https://holzke-menue.de/de/speiseplan/erwachsenen-speiseplan/schritt-login.html')
            ->withData(
                [
                    'kdnr' => config('services.holzke.login'),
                    'passwort' => config('services.holzke.password'),
                    'is_send' => 'login',
                ]
            )
            ->allowRedirect()
            ->setCookieJar($this->cookieJar)
            ->post();
    }

    public function supportsAutoOrder(): bool
    {
        return true;
    }

    public function supportsOrderUpdate(): bool
    {
        return false;
    }

    /**
     * @param Carbon $date
     *
     * @throws InvalidSelectorException
     */
    public function getMealsDataForDate(Carbon $date): array
    {
        return $this->parseResponse(
            $this->getHtml($date)
        );
    }

    /**
     * @param $response
     * @return array[]
     *
     * @throws InvalidSelectorException
     */
    private function parseResponse($response): array
    {
        return array_map(
            function ($mealElement) {
                $info = new MealInfo();
                $info->calories = $this->extractCalories($mealElement);
                $info->allergens = $this->extractAllergens($mealElement);

                $values =  [
                    'title' => $this->extractTitle($mealElement),
                    'description' => $this->extractDescription($mealElement),
                    'price' => $this->extractPrice($mealElement),
                    'external_id' => $this->extractExternalId($mealElement),
                    'info' => $info,
                ];

                return array_filter($values);
            },
            (new Document($response))->find('.meal')
        );
    }

    /**
     * @param Element $mealElement
     * @return float
     *
     * @throws InvalidSelectorException
     */
    private function extractCalories(Element $mealElement): float
    {
        return floatval($mealElement->first('.kcal')->firstChild()->text());
    }

    /**
     * @param Element $mealElement
     * @return string[]
     *
     * @throws InvalidSelectorException
     */
    private function extractAllergens(Element $mealElement): array
    {
        return array_map(function (Element $element) {
            return $element->getAttribute('title');
        }, $mealElement->first('.zusatz')->children());
    }

    /**
     * @param Element $mealElement
     * @return string
     *
     * @throws InvalidSelectorException
     */
    private function extractTitle(Element $mealElement): string
    {
        $title = $mealElement->first('h2')->text();
        preg_match('/^[\w\s]*/mu', $title, $titleMatch);

        return trim($titleMatch[0]);
    }

    /**
     * @param Element $mealElement
     * @return string
     *
     * @throws InvalidSelectorException
     */
    private function extractDescription(Element $mealElement): string
    {
        return trim($mealElement->first('.cBody')->firstChild()->text());
    }

    /**
     * @param Element $mealElement
     * @return int
     *
     * @throws InvalidSelectorException
     */
    private function extractPrice(Element $mealElement): int
    {
        $title = $mealElement->first('h2')->text();
        preg_match('/\((\S*)/', $title, $priceMatch);
        $price = preg_replace('/[,.]/', '', $priceMatch[1] ?? 1);

        return intval($price);
    }

    private function extractExternalId(Element $mealElement): string|null
    {
        $externalId = $mealElement->first('input');
        $externalId = $externalId ? $externalId->getAttribute('name') : null;

        return $externalId ? trim($externalId) : null;
    }

    /**
     * @param Carbon $date
     * @return string
     */
    public function getHtml(Carbon $date): string
    {
        return Curl::to('https://holzke-menue.de/de/speiseplan/erwachsenen-speiseplan.html')
            ->withData(['t' => $date->timestamp])
            ->setCookieFile($this->cookieJar)
            ->get();
    }

    public function placeOrder(Order $order)
    {
        $this->updateMealsCount($order);

        $this->confirmOrder();

        $order->update(
            [
                'status' => Order::STATUS_ORDERED,
                'external_id' => $this->getLastOrderId(),
            ]
        );
    }

    /**
     * @param $meal
     * @param $count
     */
    private function updateMealCount($meal, $count): void
    {
        Curl::to('https://holzke-menue.de/ajax/updateMealCount.php')
            ->withData(compact('meal', 'count'))
            ->returnResponseObject()
            ->setCookieFile($this->cookieJar)
            ->post();
    }

    private function confirmOrder(): void
    {
        Curl::to('https://holzke-menue.de/de/speiseplan/erwachsenen-speiseplan/schritt-order.html')
            ->withData(
                [
                    'info1' => config('services.holzke.order_info'),
                    'agb' => 1,
                    'zeit' => now()->timestamp,
                    'is_send' => 'yes',
                ]
            )
            ->returnResponseObject()
            ->setCookieFile($this->cookieJar)
            ->post();
    }

    /**
     * @return string|null
     *
     * @throws InvalidSelectorException
     */
    private function getLastOrderId(): ?string
    {
        $response = Curl::to('https://holzke-menue.de/de/meine-kundendaten/meine-bestellungen.html')
            ->setCookieFile($this->cookieJar)
            ->get();

        $orderChange = (new Document($response))->first('.orderChange');

        abort_unless($orderChange, Response::HTTP_INTERNAL_SERVER_ERROR, 'Could not find order number');

        return $orderChange->getAttribute('data-id');
    }

    /**
     * @throws InvalidSelectorException
     */
    public function updateOrder(Order $order)
    {
        $this->setOrderForEdit($order->external_id);

        $this->placeOrder($order);
    }

    /**
     * @param string $external_id
     */
    private function setOrderForEdit(string $external_id): void
    {
        Curl::to('https://holzke-menue.de/ajax/updateMealCount.php')
            ->withData(['vid' => $external_id])
            ->returnResponseObject()
            ->setCookieFile($this->cookieJar)
            ->post();
    }

    public function configureSchedule(Schedule $schedule): void
    {
        if (!config('services.holzke.schedule')) {
            return;
        }

        $schedule->call([$this, 'getAllUpcomingMeals'])->dailyAt('10:00');
    }

    public function getAllUpcomingMeals()
    {
        $date = today();

        if ($date->isWeekend()) {
            $date->addWeekday();
        }

        while ($this->createMealsDataForDate(Carbon::parse($date))) {
            $date->addWeekday();
        }

        $this->notifyAboutNewOrderPossibilities();
    }

    /**
     * @param Order $order
     * @return void
     */
    private function updateMealsCount(Order $order): void
    {
        foreach ($order->meals as $meal) {
            if ($meal->external_id) {
                $this->updateMealCount(
                    $meal->external_id,
                    $order->orderItems->where('meal_id', $meal->id)->sum('quantity')
                );
            }
        }
    }
}
