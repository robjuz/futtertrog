<?php

namespace App\MealProviders;

use App\Meal;
use App\Order;
use Exception;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

abstract class AbstractMealProvider
{
    public static function register(Application $app)
    {
        $app->singleton(static::class, function () {
            return new static();
        });

        $app->alias(static::class, class_basename(static::class));
    }

    public function getName(): string {
        return class_basename($this);
    }

    abstract public function getMealsDataForDate(Carbon $date): array;

    public function createMealsDataForDate(Carbon $date): Collection
    {
        $meals = collect();

        foreach ($this->getMealsDataForDate($date) as $data) {
            $meal = Meal::updateOrCreate(
                [
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'date_from' => $date->toDateString(),
                    'date_to' => $date->toDateString(),
                    'provider' => class_basename($this),
                ],
                $data
            );

            foreach ($data['variants'] ?? [] as $variantData) {
                $meal->variants()->updateOrCreate(
                    [
                        'title' => $variantData['title'],
                        'description' => $variantData['description'] ?? null,
                        'date_from' => $date->toDateString(),
                        'date_to' => $date->toDateString(),
                        'provider' => class_basename($this),
                    ],
                    $variantData);
            }

            $meals->add($meal);
        }

        return $meals;
    }

    abstract public function supportsAutoOrder(): bool;

    abstract public function supportsOrderUpdate(): bool;

    abstract public function configureSchedule(Schedule $schedule): void;

    /**
     * @param  Order[]|Collection  $orders
     */
    public function placeOrder($orders)
    {
        throw new Exception('Not implemented');
    }

    public function __toString()
    {
        return $this->getName();
    }
}
