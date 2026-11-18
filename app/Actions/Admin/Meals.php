<?php

namespace App\Actions\Admin;

use App\Events\NewOrderPossibility;
use App\Models\{Meal, User};
use Illuminate\Support\Facades\{DB, Gate, Validator};
use Illuminate\Validation\Rule;

class Meals
{
    /**
     * Integer prices are cents, as in the existing model and administrative actions.
     * @param array{title: string, date: string, price: int|float, description?: ?string, provider?: ?string, parent_id?: ?int, can_be_ordered_until?: ?string} $data
     */
    public function create(User $actor, array $data, bool $notify = false): Meal
    {
        return DB::transaction(function () use ($actor, $data, $notify): Meal {
            $actor = ActivityLogger::authorize($actor);
            Gate::forUser($actor)->authorize('create', Meal::class);
            $data = Validator::make($data, [
                'title' => ['required', 'string', 'max:255'], 'date' => ['required', 'date'],
                'description' => ['nullable', 'string'], 'price' => ['required', 'numeric', 'min:0'],
                'provider' => ['nullable', Rule::in(array_keys(app('mealProviders')))],
                'parent_id' => ['nullable', 'integer', Rule::exists('meals', 'id')->whereNull('parent_id')],
                'can_be_ordered_until' => ['nullable', 'date', 'after:date'],
            ])->validate();
            $meal = Meal::create($data);
            ActivityLogger::record($actor, 'meal.created', $meal, after: $meal->getAttributes());
            if ($notify) {
                DB::afterCommit(fn () => event(new NewOrderPossibility($meal->date)));
            }

            return $meal;
        });
    }
}
