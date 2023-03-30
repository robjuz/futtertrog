<?php

namespace App\MealProviders;

use App\Events\NewOrderPossibilities;
use App\Meal;
use App\MealProviders\Interfaces\HasWeeklyOrders;
use App\Order;
use Exception;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

abstract class AbstractMealProvider implements \JsonSerializable
{
    /**
     * @var string[]
     */
    private array $newOrderPossibilitiesDates = [];

    public static function register(Application $app): bool
    {
        $name = class_basename(static::class);

        $configKey = Str::snake($name);

        if (config("services.{$configKey}")) {
            if (!config("services.{$configKey}.enabled")) {
                return false;
            }
        }


        $app->singleton(static::class, static::class);

        $app->alias(static::class, $name);

        return true;
    }

    public function createMealsDataForDate(Carbon $date): int
    {
        $meals = collect();

        foreach ($this->getMealsDataForDate($date) as $data) {
            $meal = Meal::updateOrCreate(
                [
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'date' => $date->toDateString(),
                    'provider' => $this->getKey(),
                ],
                $data
            );

            foreach ($data['variants'] ?? [] as $variantData) {
                $meal->variants()->updateOrCreate(
                    [
                        'title' => $variantData['title'],
                        'description' => $variantData['description'] ?? null,
                        'date' => $date->toDateString(),
                        'provider' => $this->getKey(),
                    ],
                    $variantData
                );
            }

            $meals->add($meal);
        }

        if ($meals->where('wasRecentlyCreated')->isNotEmpty()) {
            $this->newOrderPossibilitiesDates[] = $date->toDateString();
        }

        return $meals->count();
    }

    abstract public function getMealsDataForDate(Carbon $date): array;

    public function getKey(): string
    {
        return class_basename($this);
    }

    abstract public function supportsAutoOrder(): bool;

    abstract public function supportsOrderUpdate(): bool;

    abstract public function configureSchedule(Schedule $schedule): void;

    public function placeOrder(Order $order)
    {
        throw new Exception('Not implemented');
    }

    /**
     * Per default create one order pro day
     *
     * @return Order
     */
    public function getOrder($date = null): Order
    {
        $date = $date ? Carbon::parse($date) : now();

        $query = Order::query();

        if ($this instanceof HasWeeklyOrders) {
            $query = $query->whereHas('meals', function (Builder $query) use ($date) {
                $query
                    ->whereDate('date', '>=', $date->startOfWeek())
                    ->whereDate('date', '<=', $date->endOfWeek());
            });
        } else {
            $query = $query->whereHas('meals', fn(Builder $query) => $query->whereDate('date', $date));
        }


        return $query->firstOrCreate(['provider' => $this->getKey()], ['status' => Order::STATUS_OPEN]);
    }

    public function notifyAboutNewOrderPossibilities()
    {
        if (count($this->newOrderPossibilitiesDates) > 0) {
            event(new NewOrderPossibilities($this->newOrderPossibilitiesDates));
        }
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->__toString()
        ];
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return $this->getKey();
    }
}
