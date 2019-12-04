<?php

namespace App\Services;

use DiDom\Document;
use Ixudra\Curl\Facades\Curl;

class CallAPizzaService
{
    private $location;

    private $meals;

    public function __construct()
    {
        $this->location = config('services.call_a_pizza.location');
        $this->meals = config('services.call_a_pizza.meals');
    }

    /**
     * @return array
     */
    public function getMealsForDate($date = null)
    {
        $meals = [];

        foreach ($this->meals as $meal) {
            $response = Curl::to("https://www.call-a-pizza.de/{$this->location}/{$meal}")
                ->get();
            $meals += $this->parseResponse($response);
        }

        return $meals;
    }

    /**
     * @param $response
     * @return \DiDom\Element[]|\DOMElement[]
     */
    public function parseResponse($response)
    {
        $meals = [];

        foreach ((new Document($response))->find('.item') as $mealElement) {
            $productText = $mealElement->first('.product-text');
            if (! $productText OR ! ($name = $productText->first('.name a'))) {
                continue;
            }

            $name = trim($name->text());

            $descriptionNode = $mealElement->first('.description');

            $description = $descriptionNode->innerHtml();
            $description = preg_replace('/<sup.*<\/sup>/', '', $description);
            $description = preg_replace('/<a.*<\/a>/', '', $description);
            $description = preg_replace('/<br>/', '', $description);
            $description = trim($description);

            $image = $mealElement->first('.product-img img');

            foreach ($mealElement->find('.add-to-cart') as $priceInfo) {

                $priceTitle = strip_tags($priceInfo->find('.price_box_title')[0]->text());
                $priceText = $priceInfo->find('.price_box_price')[0]->text();
                $priceText = preg_replace('/\D+$/', '', $priceText);
                $priceText = str_replace(',', '.', $priceText);

                $meals[] = [
                    'title' => $name.'('.$priceTitle.')',
                    'description' => $description,
                    'price' => floatval($priceText),
                    'image' => $image->getAttribute('src') ?: $image->getAttribute('data-src'),
                ];
            }
        }

        return $meals;
    }
}