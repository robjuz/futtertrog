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
use Illuminate\Support\Facades\URL;
use Ixudra\Curl\Facades\Curl;
use Symfony\Component\HttpFoundation\Response;

class Holzke extends AbstractMealProvider implements HasWeeklyOrders
{

    private string $cookieJar;

    private bool $isLoggedIn = false;

    private string $baseUrl = 'https://bestellung-holzke-menue.de';
    private string $loginUrl = 'https://bestellung-holzke-menue.de';

    const LOGIN_URL = '/en/accounts/login/';
    const MEAL_URL = '/en/sammel/eb/';

    public function __construct()
    {
        $this->cookieJar = storage_path('holzke_cookie.txt');
    }

    private function getUrl($url) {
        return URL::format($this->baseUrl, $url) . '/';
    }

    private function login(): void
    {
        if ($this->isLoggedIn) {
            return;
        }

        if (!config('services.holzke.login') || !config('services.holzke.password')) {
            return;
        }

        /** @var Element $response */
        $response = Curl::to($this->loginUrl)
            ->allowRedirect()
            ->setCookieJar($this->cookieJar)
            ->withResponseHeaders()
            ->get();


        $csrf = (new Document($response))->first('[name=csrfmiddlewaretoken]')->attr('value');

        $response = Curl::to($this->getUrl(self::LOGIN_URL))
            ->withData(
                [
                    'login' => config('services.holzke.login'),
                    'password' => config('services.holzke.password'),
                    'csrfmiddlewaretoken' => $csrf,
                ]
            )
            ->allowRedirect()
            ->setCookieFile($this->cookieJar)
            ->setCookieJar($this->cookieJar)
            ->withResponseHeaders()
            ->returnResponseObject()
            ->post();

        $this->baseUrl = 'https://' . parse_url($response->headers['location'][0], PHP_URL_HOST);

        $this->isLoggedIn = true;

    }

    public function supportsAutoOrder(): bool
    {
        return false;
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
        $items = (new Document($response))->find('.menu-table tr');
        array_shift($items); //remove headings row
        $items =  array_map(
            function ($mealElement) {
                $info = new MealInfo();
//                $info->calories = $this->extractCalories($mealElement);
//                $info->allergens = $this->extractAllergens($mealElement);

                return [
                    'title' => $this->extractTitle($mealElement),
                    'description' => $this->extractDescription($mealElement),
                    'price' => $this->extractPrice($mealElement),
                    'external_id' => $this->extractExternalId($mealElement),
                    'info' => $info,
                ];
            },
            $items
        );

        return collect($items)->whereNotNull('external_id')->toArray();
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
        $title = $mealElement->first('.mealtitel')->firstChild();
        if (!$title) {
            return '';
        }

        $title = $title->text();
        preg_match('/[\w\s]*/mu', $title, $titleMatch);

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
        $mealText = $mealElement->first('#mealtext');
        if (!$mealText) {
            return '';
        }

        $description =  $mealText->firstChild();
        if (!$description) {
            return '';
        }

        return trim($description->text());
    }

    /**
     * @param Element $mealElement
     * @return int
     *
     * @throws InvalidSelectorException
     */
    private function extractPrice(Element $mealElement): int
    {
        $priceElement = $mealElement->first('price');
        if (!$priceElement) {
            return 0;
        }

        $priceText = $priceElement->text();
        $price = preg_replace('/\D*/', '', $priceText) ?? 0;

        return intval($price);
    }

    private function extractExternalId(Element $mealElement): string|null
    {
        $externalId = $mealElement->first('input');
        if (!$externalId) {
            return null;
        }

        return trim($externalId->getAttribute('name'));
    }

    /**
     * @param Carbon $date
     * @return string
     */
    public function getHtml(Carbon $date): string
    {
        $this->login();

        $response = Curl::to($this->getUrl(self::MEAL_URL))
            ->setCookieFile($this->cookieJar)
            ->withResponseHeaders()
            ->returnResponseObject()
            ->allowRedirect()
            ->get();

        $urlParts = explode(
            '/',
            trim(
                $response->headers['location'],
                '/',
            ),
        );

        array_pop($urlParts);
        array_pop($urlParts);
        $urlParts[] = $date->toDateString();
        $urlParts[] = $date->toDateString();

        $url = $this->getUrl(join('/', $urlParts));

        return Curl::to($url)
            ->setCookieFile($this->cookieJar)
            ->get();
    }

    public function configureSchedule(Schedule $schedule): void
    {
        if (!config('services.holzke.schedule')) {
            return;
        }

        $schedule->call([$this, 'getAllUpcomingMeals'])->dailyAt('10:00');
        $schedule->call([$this, 'autoOrder'])->weekdays()->at('7:20');
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

    public function autoOrder(): void
    {
        $order = $this->getOrder(now());

        if ($order->canBeUpdated()) {
            $order->updateOrder();
        } else {
            if ($order->canBeAutoOrdered()) {
                $order->placeOrder();
            }
        }
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
    protected function getLastOrderId(): ?string
    {
        $response = Curl::to('https://holzke-menue.de/de/meine-kundendaten/meine-bestellungen.html')
            ->setCookieFile($this->cookieJar)
            ->get();

        $orderChange = (new Document($response))->first('.orderChange');

        abort_unless($orderChange, Response::HTTP_INTERNAL_SERVER_ERROR, 'Could not find order number');

        return $orderChange->getAttribute('data-id');
    }


}
