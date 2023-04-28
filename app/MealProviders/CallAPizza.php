<?php

namespace App\MealProviders;

use DiDom\Document;
use Illuminate\Console\Scheduling\Schedule;
use Ixudra\Curl\Facades\Curl;

class CallAPizza extends AbstractMealProvider
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
    public function getMealsDataForDate($date = null): array
    {
        $meals = [];

        foreach ($this->meals as $meal) {
            $response = Curl::to("https://www.call-a-pizza.de/{$this->location}/{$meal}")
                ->get();
            $meals = array_merge($meals, $this->parseResponse($response));
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
            if (! $productText or ! ($name = $productText->first('.name a'))) {
                continue;
            }

            $descriptionNode = $mealElement->first('.description');

            foreach ($descriptionNode->children() as $childNode) {
                if ($childNode->isElementNode()) {
                    $childNode->remove();
                }
            }

            $image = $mealElement->first('.product-img img');

            $meal = [
                'title' => trim($name->text()),
                'external_id' => trim($name->text()),
                'description' => trim($descriptionNode->innerHtml()),
                'image' => $image->getAttribute('src') ?: $image->getAttribute('data-src'),
                'variants' => [],
            ];

            foreach ($mealElement->find('.add-to-cart') as $priceInfo) {
                try {
                    $priceTitle = strip_tags($priceInfo->first('.price_box_title')->text());
                    $priceText = $priceInfo->first('.price_box_price')->text();
                    $priceText = preg_replace('/\D+$/', '', $priceText);
                    $priceText = preg_replace('/[,\.]/', '', $priceText);

                    $meal['variants'][] = [
                        'title' => '(' . $priceTitle . ')',
                        'price' => intval($priceText),
                    ];
                } catch (\Throwable $e) {
                }
            }

            // ignore entries with no variants (probably not available)
            if ($meal['variants']) {
                $meals[] = $meal;
            }
        }

        return $meals;
    }

    public function getName(): string
    {
        return 'Call a Pizza';
    }

    public function supportsAutoOrder(): bool
    {
        return false;
    }

    public function supportsOrderUpdate(): bool
    {
        return false;
    }

    public function configureSchedule(Schedule $schedule): void
    {
        // TODO: Implement configureSchedule() method.
    }
}
