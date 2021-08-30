<?php

namespace App\MealProviders;

use App\Meal;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Carbon;

abstract class AbstractMealProvider
{

    public static function register(Application $app)
    {
        $app->singleton(static::class, function () {
            return new static();
        });
    }

    abstract public function getName(): string;

    abstract public function getMealsForDate(Carbon $date): array;
}
