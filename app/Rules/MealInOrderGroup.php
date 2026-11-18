<?php

namespace App\Rules;

use App\MealProviders\Interfaces\HasWeeklyOrders;
use App\Models\Meal;
use App\Models\Order;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class MealInOrderGroup implements ValidationRule
{
    public function __construct(private Order $order) {}

    /** Match the provider's daily/weekly assignment without creating an order during validation. */
    public static function meals(Order $order): Builder
    {
        $date = $order->meals()->min('date');
        $query = Meal::query();
        if ($date === null) {
            return $query->whereRaw('1 = 0');
        }
        $provider = $order->provider;
        $query->where(function (Builder $query) use ($provider): void {
            $query->where('provider', $provider->getKey());
            if ($provider->getKey() === 'Internal') {
                $query->orWhereNull('provider');
            }
        });
        $date = Carbon::parse($date);
        if ($provider instanceof HasWeeklyOrders) {
            return $query->whereDate('date', '>=', $date->copy()->startOfWeek())
                ->whereDate('date', '<=', $date->copy()->endOfWeek());
        }

        return $query->whereDate('date', $date);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!self::meals($this->order)->whereKey($value)->exists()) {
            $fail(__('The meal must belong to the same collective order.'));
        }
    }
}
