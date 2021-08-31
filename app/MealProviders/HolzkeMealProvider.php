<?php

namespace App\MealProviders;

use App\Meal;
use App\MealInfo;
use App\Order;
use App\OrderItem;
use DiDom\Document;
use DiDom\Element;
use DiDom\Exceptions\InvalidSelectorException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Ixudra\Curl\Facades\Curl;
use Symfony\Component\HttpFoundation\Response;

class HolzkeMealProvider extends AbstractMealProvider
{
    private string $cookieJar = '';

    public function __construct()
    {
        $this->cookieJar = storage_path('holzke_cookie.txt');

        $this->login();
    }

    public function getName(): string
    {
        return 'Holzke';
    }

    public function supportsAutoOrder()
    {
        return true;
    }

    private function login(): void
    {
        Curl::to('https://holzke-menue.de/de/speiseplan/erwachsenen-speiseplan/schritt-login.html')
            ->withData(
                [
                    'kdnr' => config('services.holzke.login'),
                    'passwort' => config('services.holzke.password'),
                    'is_send' => 'login',
                ]
            )
            ->setCookieJar($this->cookieJar)
            ->post();
    }

    /**
     * @param  Carbon  $date
     * @throws InvalidSelectorException
     */
    public function getMealsForDate(Carbon $date): array
    {
        return $this->parseResponse(
            $this->getHtml($date)
        );
    }

    /**
     * @param  Carbon  $date
     * @return string
     */
    public function getHtml(Carbon $date): string
    {
        return Curl::to('https://holzke-menue.de/de/speiseplan/erwachsenen-speiseplan.html')
            ->withData(['t' => $date->timestamp])
            ->setCookieFile(storage_path('holtzke_cookie.txt'))
            ->get();
    }

    /**
     * @param $response
     * @return array[]
     * @throws InvalidSelectorException
     */
    private function parseResponse($response): array
    {
        return array_map(
            function ($mealElement) {
                $info = new MealInfo();
                $info->calories = $this->extractCalories($mealElement);
                $info->allergens = $this->extractAllergens($mealElement);

                return [
                    'title' => $this->extractTitle($mealElement),
                    'description' => $this->extractDescription($mealElement),
                    'price' => $this->extractPrice($mealElement),
                    'external_id' => $this->extractExternalId($mealElement),
                    'info' => $info,
                ];
            },
            (new Document($response))->find('.meal')
        );
    }

    /**
     * @param  Element  $mealElement
     * @return string
     * @throws InvalidSelectorException
     */
    private function extractTitle(Element $mealElement): string
    {
        $title = $mealElement->first('h2')->text();
        preg_match('/^[\w\s]*/mu', $title, $titleMatch);

        return trim($titleMatch[0]);
    }

    /**
     * @param  Element  $mealElement
     * @return string
     * @throws InvalidSelectorException
     */
    private function extractDescription(Element $mealElement): string
    {
        return trim($mealElement->first('.cBody')->firstChild()->text());
    }

    /**
     * @param  Element  $mealElement
     * @return float
     * @throws InvalidSelectorException
     */
    private function extractCalories(Element $mealElement): float
    {
        return floatval($mealElement->first('.kcal')->firstChild()->text());
    }

    /**
     * @param  Element  $mealElement
     * @return int
     * @throws InvalidSelectorException
     */
    private function extractPrice(Element $mealElement): int
    {
        $title = $mealElement->first('h2')->text();
        preg_match('/\((\S*)/', $title, $priceMatch);
        $price = preg_replace('/[,.]/', '', $priceMatch[1] ?? 1);

        return intval($price);
    }

    /**
     * @param  Element  $mealElement
     * @return string[]
     * @throws InvalidSelectorException
     */
    private function extractAllergens(Element $mealElement): array
    {
        return array_map(function (Element $element) {
            return $element->getAttribute('title');
        }, $mealElement->first('.zusatz')->children());
    }

    /**
     * @param  Element  $mealElement
     * @return string
     * @throws InvalidSelectorException
     */
    private function extractExternalId(Element $mealElement): string
    {
        $externalId = $mealElement->first('input');
        $externalId = $externalId ? $externalId->getAttribute('name') : null;

        return trim($externalId);
    }

    /**
     * @param  Order[]|Collection  $orders
     * @throws InvalidSelectorException
     */
    public function placeOrder($orders)
    {
        $mealsToOrderExternalIds = $this->extractMealIds($orders);

        foreach ($mealsToOrderExternalIds as $externalId => $count) {
            $this->updateMealCount($externalId, $count);
        }

        $this->confirmOrder();

        Order::whereKey($orders->modelKeys())->update(
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
            ->setCookieFile(storage_path('holtzke_cookie.txt'))
            ->post();
    }

    private function confirmOrder(): void
    {
        Curl::to('https://holzke-menue.de/de/speiseplan/erwachsenen-speiseplan/schritt-order.html')
            ->withData(
                [
                    'info1' => 'Eingang Paradiesgarten 4.OG',
                    'agb' => 1,
                    'zeit' => now()->timestamp,
                    'is_send' => 'yes',
                ]
            )
            ->setCookieFile(storage_path('holtzke_cookie.txt'))
            ->post();
    }

    /**
     * @return string|null
     * @throws InvalidSelectorException
     */
    private function getLastOrderId(): ?string
    {
        $response = Curl::to('https://holzke-menue.de/de/meine-kundendaten/meine-bestellungen.html')
            ->setCookieFile(storage_path('holtzke_cookie.txt'))
            ->get();

        $orderChange = (new Document($response))->first('.orderChange');

        abort_if(! $orderChange, Response::HTTP_INTERNAL_SERVER_ERROR, 'Could not find order number');

        return $orderChange->getAttribute('data-id');
    }

    /**
     * @param  OrderItem  $orderItem
     * @throws InvalidSelectorException
     */
    public function updateOrder(OrderItem $orderItem)
    {
        $order = $orderItem->order;
        $meal = $orderItem->meal;

        $this->setOrderForEdit($order->external_id);

        $this->updateMealCount(
            $meal->external_id,
            $order->orderItems()->whereMealId($meal->id)->sum('quantity')
        );

        $this->confirmOrder();

        $order->update(
            [
                'status' => Order::STATUS_ORDERED,
                'external_id' => $this->getLastOrderId(),
            ]
        );
    }

    /**
     * @param  string  $external_id
     */
    private function setOrderForEdit(string $external_id): void
    {
        Curl::to('https://holzke-menue.de/ajax/updateMealCount.php')
            ->withData(['vid' => $external_id])
            ->returnResponseObject()
            ->setCookieFile(storage_path('holtzke_cookie.txt'))
            ->post();
    }

    /**
     * @param Collection $orders
     * @return array
     */
    private function extractMealIds(Collection $orders): array
    {
        $mealsToOrderExternalIds = [];

        foreach ($orders as $order) {
            foreach ($order->orderItems as $orderItem) {
                if (! isset($mealsToOrderExternalIds[$orderItem->meal->external_id])) {
                    $mealsToOrderExternalIds[$orderItem->meal->external_id] = 0;
                }

                $mealsToOrderExternalIds[$orderItem->meal->external_id] += $orderItem->quantity;
            }
        }

        return $mealsToOrderExternalIds;
    }
}
