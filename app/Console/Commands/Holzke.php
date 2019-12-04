<?php

namespace App\Console\Commands;

use App\Events\NewOrderPossibility;
use App\Meal;
use App\Services\HolzkeService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Class Holzke.
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

            $meals = $holzke->getMealsForDate($date);

            foreach ( $meals as $mealElement) {
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
     * @param \Illuminate\Support\Carbon $date
     * @return \App\Meal
     */
    public function createOrUpdateMeal($mealElement, Carbon $date): Meal
    {

        return Meal::updateOrCreate(
            [
                'title' => $mealElement['title'],
                'date_from' => $date->toDateString(),
                'date_to' => $date->toDateString(),
                'provider' => Meal::PROVIDER_HOLZKE,
            ],
            [
                'description' => $mealElement['description'],
                'price' => $mealElement['price'],
            ]
        );
    }
}
