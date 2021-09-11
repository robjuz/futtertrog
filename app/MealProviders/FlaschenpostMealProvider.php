<?php

namespace App\MealProviders;

use DiDom\Document;
use DiDom\Query;
use Illuminate\Console\Scheduling\Schedule;
use Ixudra\Curl\Facades\Curl;
use \CJSON;

class FlaschenpostMealProvider extends AbstractMealProvider
{
    private string $zipcode;

    private array $categories;

    public function __construct()
    {
        $this->zipcode = config('services.flaschenpost.zipcode');
        $this->categories = config('services.flaschenpost.categories');

        $this->cookieJar = storage_path('flaschenpost_cookie.txt');

        $this->setZipCode();
    }

    private function setZipCode(): void
    {
        Curl::to("https://www.flaschenpost.de/api/zipcode/{$this->zipcode}/switch")
            ->setCookieJar($this->cookieJar)
            ->get();
    }

    /**
     * @return array
     */
    public function getMealsDataForDate($date = null): array
    {
        $meals = [];

        foreach ($this->categories as $category) {
            $response = Curl::to("https://www.flaschenpost.de/{$category}")
                ->setCookieJar($this->cookieJar)
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

        $scriptTag = (new Document($response))->first('.page_main > .page-container');

        if (!$scriptTag) {
            return $meals;
        }

        $scriptText = $scriptTag->text();

        $productsText = [];
        preg_match("/\[{.*}]/", $scriptText, $productsText);

        if (!isset($productsText[0])) {
            return $meals;
        }

        $products = collect(CJSON::decode($productsText[0]));

        $brands = $products->groupBy('brand');

        foreach ($brands as $brand => $products) {
            $meal = [
                'title' => $brand,
                'variants' => [],
            ];

            foreach ($products as $product) {
                $meal['variants'][] = [
                    'title' => $product['name'],
                    'price' => $product['price'] + $product['metric4'],
                ];
            }

            $meals[] = $meal;
        }


        return $meals;
    }

    public function getName(): string
    {
        return 'Flaschenpost';
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
