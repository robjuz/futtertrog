<?php

namespace App\Console\Commands;

use App\Events\NewOrderPossibility;
use App\Meal;
use App\Services\HolzkeService;
use DiDom\Document;
use DiDom\Element;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Class Holzke.
 *
 * @codeCoverageIgnore
 */
class Holzke extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:holzke';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import coming meals from Holzke';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param \App\Services\HolzkeService $holzke
     * @return mixed
     */
    public function handle(HolzkeService $holzke)
    {
        //get data
        $date = today();
        if ($date->isWeekend()) {
            $date->addWeekday();
        }

        do {
            $createdMeals = false;

            $meals = $this->parseResponse($holzke->getMealsForDate($date));

            foreach ($meals as $mealElement) {
                $meal = $this->createOrUpdateMeal($mealElement, $date);

                if ($meal->wasRecentlyCreated) {
                    $createdMeals = true;
                }
            }

            if ($createdMeals) {
                event(new NewOrderPossibility($date));
            }

            $date->addWeekday();
        } while (count($meals));
    }

    /**
     * @param \DiDom\Element $mealElement
     * @param \Illuminate\Support\Carbon $date
     * @return \App\Meal
     */
    public function createOrUpdateMeal(Element $mealElement, Carbon $date): Meal
    {
        $title = $mealElement->find('h2')[0]->text();

        preg_match('/^[\w\s]*/mu', $title, $titleMatch);
        preg_match('/\((\S*)/', $title, $priceMatch);

        return Meal::updateOrCreate(
            [
                'title' => trim($titleMatch[0]),
                'date_from' => $date->toDateString(),
                'date_to' => $date->toDateString(),
                'provider' => Meal::PROVIDER_HOLZKE,
            ],
            [
                'description' => trim($mealElement->find('.cBody')[0]->removeChildren()[0]->text()),
                'price' => floatval(str_replace(',', '.', $priceMatch[1])),
            ]
        );
    }

    /**
     * @param $response
     * @return \DiDom\Element[]|\DOMElement[]
     */
    public function parseResponse($response)
    {
        $meals = (new Document($response))->find('.meal');

        return $meals;
    }
}
