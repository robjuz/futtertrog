<?php

namespace App\MealProviders;

use App\MealInfo;
use CJSON;
use DiDom\Document;
use Illuminate\Console\Scheduling\Schedule;
use Ixudra\Curl\Facades\Curl;

class Flaschenpost extends AbstractMealProvider
{
    private string $zipcode;

    private array $categories;
    private string $cookieJar;

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

        if (! $scriptTag) {
            return $meals;
        }

        $scriptText = $scriptTag->text();

        $productsText = [];
        preg_match_all("/\[{.*}]/", $scriptText, $productsText);

        if (! isset($productsText[0][1])) {
            return $meals;
        }

        $products = collect(CJSON::decode($productsText[0][1]));

        $products = $products->sortBy('name');

        foreach ($products as $product) {
            foreach ($product['articles'] as $article) {
                $info = new MealInfo();
                $info->deposit = $article['deposit'];

                $meals[] = [
                    'title' => $product['name'],
                    'description' => join('<br>', [$article['shortDescription'], $article['pricePerUnit']]),
                    'price' => $article['trackingNetPrice'] + $article['trackingPriceDuty'],
                    'image' => $product['imagePath'].$product['articleIdForImage'].'.png',
                    'info' => $info,
                ];
            }
        }

        return $meals;
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
