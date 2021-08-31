<?php

namespace App\MealProviders;

use App\Meal;
use App\Order;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Collection;
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

    public function supportsAutoOrder()
    {
        return false;
    }

    public function supportsOrderUpdate()
    {
        return false;
    }

    /**
     * @param  Order[]|Collection  $orders
     */
    public function placeOrder($orders)
    {
        throw new \Exception('Not implemented');
    }

    public function __toString()
    {
        return $this->getName();
    }
}
