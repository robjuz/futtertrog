<?php

namespace App\Console\Commands;

use App\Events\NewOrderPossibilities;
use App\Meal;
use App\Services\HolzkeService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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

        $newOrderPossibilities = new Collection();

        do {
            $meals = $holzke->getMealsForDate($date);

            foreach ($meals as $mealElement) {
                $meal = $this->createOrUpdateMeal($mealElement, $date);

                if ($meal->wasRecentlyCreated) {
                    $newOrderPossibilities->add($date->copy());
                }
            }

            $date->addWeekday();
        } while (count($meals));

        if (count($newOrderPossibilities->unique()) > 0) {
            event(new NewOrderPossibilities($newOrderPossibilities));
        }
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
