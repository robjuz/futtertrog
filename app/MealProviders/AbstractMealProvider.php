<?php

namespace App\MealProviders;

use App\Order;
use Illuminate\Console\Scheduling\Schedule;
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

    abstract public function getMealsDataForDate(Carbon $date): array;

    abstract public function supportsAutoOrder(): bool;

    abstract public function supportsOrderUpdate(): bool;

    abstract public function configureSchedule(Schedule $schedule): void;

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
