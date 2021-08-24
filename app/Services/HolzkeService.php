<?php

namespace App\Services;

use App\MealInfo;
use App\Order;
use App\OrderItem;
use DiDom\Document;
use DiDom\Element;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Ixudra\Curl\Facades\Curl;

class HolzkeService
{
    protected $cookieJar = '';

    public function __construct()
    {
        $this->cookieJar = storage_path('holtzke_cookie.txt');

        $this->login();
    }

    private function login()
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
     * @param \Illuminate\Support\Carbon $date
     * @return mixed
     */
    public function getMealsForDate(Carbon $date)
    {
        $response = $this->getHtml($date);

        return $this->parseResponse($response);
    }

    /**
     * @param \Illuminate\Support\Carbon $date
     * @return string
     */
    public function getHtml(Carbon $date)
    {
        return Curl::to('https://holzke-menue.de/de/speiseplan/erwachsenen-speiseplan.html')
            ->withData(['t' => $date->timestamp])
            ->setCookieFile(storage_path('holtzke_cookie.txt'))
            ->get();
    }

    /**
     * @param $response
     * @return array[]
     * @throws \DiDom\Exceptions\InvalidSelectorException
     */
    private function parseResponse($response)
    {
        return array_map(
            function ($mealElement) {

                $info = new MealInfo();
                $info->calories = $this->extractCalories($mealElement);

                return [
                    'title' => $this->extractTitle($mealElement),
                    'description' => $this->extractDescription($mealElement),
                    'price' => $this->extractPrice($mealElement),
                    'external_id' => $this->extractExternalId($mealElement),
                    'info' => $info
                ];
            },
            (new Document($response))->find('.meal')
        );
    }

    private function extractTitle(Element $mealElement)
    {
        $title = $mealElement->first('h2')->text();
        preg_match('/^[\w\s]*/mu', $title, $titleMatch);

        return trim($titleMatch[0]);
    }

    private function extractDescription(Element $mealElement)
    {
        return trim($mealElement->first('.cBody')->firstChild()->text());
    }

    private function extractCalories(Element $mealElement)
    {
        return floatval($mealElement->first('.kcal')->firstChild()->text());
    }

    private function extractPrice(Element $mealElement)
    {
        $title = $mealElement->first('h2')->text();
        preg_match('/\((\S*)/', $title, $priceMatch);
        $price = preg_replace('/[,\.]/', '', $priceMatch[1] ?? 1);

        return intval($price);
    }

    private function extractExternalId(Element $mealElement)
    {
        $externalId = $mealElement->first('input');
        $externalId = $externalId ? $externalId->getAttribute('name') : null;

        return trim($externalId);
    }

    /**
     * @param Order[]|Collection $orders
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

    private function getLastOrderId()
    {
        $response = Curl::to('https://holzke-menue.de/de/meine-kundendaten/meine-bestellungen.html')
            ->setCookieFile(storage_path('holtzke_cookie.txt'))
            ->get();

        $orderChange = (new Document($response))->first('.orderChange');

        abort_if(! $orderChange, Response::HTTP_INTERNAL_SERVER_ERROR, 'Could not find order number');

        return $orderChange->getAttribute('data-id');
    }

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
