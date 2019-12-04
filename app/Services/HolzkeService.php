<?php

namespace App\Services;

use App\Meal;
use DiDom\Document;
use DiDom\Element;
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
            ->withData([
                'kdnr' => config('services.holzke.login'),
                'passwort' => config('services.holzke.password'),
                'is_send' => 'login',
            ])
            ->setCookieJar($this->cookieJar)
            ->post();
    }

    /**
     * @param \Illuminate\Support\Carbon $date
     * @return mixed
     */
    public function getMealsForDate(Carbon $date)
    {
        $response =  Curl::to('https://holzke-menue.de/de/speiseplan/erwachsenen-speiseplan.html')
            ->withData(['t' => $date->timestamp])
            ->setCookieFile(storage_path('holtzke_cookie.txt'))
            ->get();

        return $this->parseResponse($response);
    }

    /**
     * @param $response
     * @return \DiDom\Element[]|\DOMElement[]
     */
    public function parseResponse($response)
    {
        $meals = [];

        foreach((new Document($response))->find('.meal') as $mealElement) {
            $title = $mealElement->find('h2')[0]->text();

            preg_match('/^[\w\s]*/mu', $title, $titleMatch);
            preg_match('/\((\S*)/', $title, $priceMatch);

            $meals[] = [
                'title' => trim($titleMatch[0]),
                'description' => trim($mealElement->find('.cBody')[0]->removeChildren()[0]->text()),
                'price' => floatval(str_replace(',', '.', $priceMatch[1])),
            ];
        }
        return $meals;
    }
}
