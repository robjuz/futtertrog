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

        $newOrderPossibilitiesDates = new Collection();

        do {
            $meals = $holzke->getMealsForDate($date);

            foreach ($meals as $mealElement) {
                $meal = $this->createOrUpdateMeal($mealElement, $date);

                if ($meal->wasRecentlyCreated) {
                    $newOrderPossibilitiesDates->add(clone $date);
                }
            }

            $date->addWeekday();
        } while (count($meals));

        $newOrderPossibilitiesDates = $newOrderPossibilitiesDates->unique();

        if (count($newOrderPossibilitiesDates) > 0) {
            event(new NewOrderPossibilities($newOrderPossibilitiesDates));
        }

        /** @var Carbon $date */
        foreach ($newOrderPossibilitiesDates as $date) {
            $this->info(__('New order possibility for :day', ['day' => $date->toDateString()]));
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
                'external_id' => $mealElement['external_id'],
                'description' => $mealElement['description'],
                'price' => $mealElement['price'],
            ]
        );
    }
}
