<?php

namespace App\Services;

use App\Events\NewOrderPossibilities;
use App\Meal;
use App\MealProviders\AbstractMealProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MealService
{
    private AbstractMealProvider $provider;

    /**
     * @var Collection|string[]
     */
    private Collection $newOrderPossibilitiesDates;

    public function __construct()
    {
        $this->newOrderPossibilitiesDates = new Collection();
    }

    /**
     * @param Carbon $date
     * @return int The number of imported meals
     * @throws \Exception
     */
    public function getMealsForDate(Carbon $date): int
    {
        $meals = $this->getProvider()->getMealsDataForDate($date);
        foreach ($meals as $data) {
            $meal = Meal::updateOrCreate(
                [
                    'title' => $data['title'],
                    'date_from' => $date->toDateString(),
                    'date_to' => $date->toDateString(),
                    'provider' => get_class($this->getProvider()),
                ],
                $data
            );

            foreach ($data['variants'] ?? [] as $variantData) {
                $meal->variants()->updateOrCreate(
                    [
                        'title' => $variantData['title'],
                        'date_from' => $date->toDateString(),
                        'date_to' => $date->toDateString(),
                        'provider' => get_class($this->getProvider()),
                    ],
                    $variantData);
            }

            if ($meal->wasRecentlyCreated) {
                $this->newOrderPossibilitiesDates->add($date->toDateString());
            }
        }

        return count($meals);
    }

    /**
     * @return AbstractMealProvider
     * @throws \Exception
     */
    public function getProvider(): AbstractMealProvider
    {
        if (isset($this->provider)) {
            return $this->provider;
        }

        throw new \Exception('You need to choose a meal provider first');
    }

    /**
     * @param AbstractMealProvider $provider
     */
    public function setProvider(AbstractMealProvider $provider): MealService
    {
        $this->provider = $provider;

        return $this;
    }

    public function notify()
    {
        if (count($this->newOrderPossibilitiesDates) > 0) {
            event(new NewOrderPossibilities($this->newOrderPossibilitiesDates));
        }
    }
}
