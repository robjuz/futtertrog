<?php

namespace App\Services;

use App\Order;
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
    public function parseResponse($response)
    {
        return array_map(function($mealElement) {
            return [
                'title'         => $this->extractTitle($mealElement),
                'description'   => $this->extractDescription($mealElement),
                'price'         => $this->extractPrice($mealElement),
                'external_id'   => $this->extractExternalId($mealElement)
            ];
        }, (new Document($response))->find('.meal'));
    }

    private function extractTitle(Element $mealElement)
    {
        $title = $mealElement->first('h2')->text();
        preg_match('/^[\w\s]*/mu', $title, $titleMatch);
        return trim($titleMatch[0]);
    }

    private function extractExternalId(Element $mealElement)
    {
        $externalId = $mealElement->first('input');
        $externalId ? $externalId->getAttribute('name') : null;
        return trim($externalId);
    }

    private function extractDescription(Element $mealElement)
    {
        $description = $mealElement->first('.cBody');
        $description->removeChildren();
        $description = $description->text();
        return trim($description);
    }

    private function extractPrice(Element $mealElement)
    {
        $title = $mealElement->first('h2')->text();
        preg_match('/\((\S*)/', $title, $priceMatch);
        $price = preg_replace('/[,\.]/', '', $priceMatch[1] ?? 1);
        return intval($price);
    }

    /**
     * @param Order[]|Collection $orders
     */
    public function placeOrder($orders)
    {
        $mealsToOrder = [];

        foreach ($orders as $order) {
            foreach ($order->orderItems as $orderItem) {
                abort_if(
                    ! $orderItem->meal->external_id,
                    Response::HTTP_BAD_REQUEST,
                    __('Unable to place order. Meal external ID missing')
                );

                $mealsToOrder[$orderItem->meal->external_id]++;
            }
        }

        foreach ($mealsToOrder as $meal => $count) {
            Curl::to('https://holzke-menue.de/ajax/updateMealCount.php')
                ->withData(compact('meal', 'count'))
                ->returnResponseObject()
                ->setCookieFile(storage_path('holtzke_cookie.txt'))
                ->post();
        }

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

        Order::whereKey($orders)->update(['status' => Order::STATUS_ORDERED]);
    }
}
