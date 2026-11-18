<?php

namespace App\Actions\Admin;

use App\Models\Meal;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ImportMeals
{
    public function execute(User $actor, string $provider, string $date, bool $notify = false): int
    {
        $actor = ActivityLogger::authorize($actor);
        Gate::forUser($actor)->authorize('create', Meal::class);
        Validator::make(compact('provider', 'date'), [
            'date' => 'required|date', 'provider' => ['required', Rule::in(array_keys(app('mealProviders')))],
        ])->validate();

        return DB::transaction(function () use ($actor, $provider, $date, $notify): int {
            $mealProvider = app($provider);
            $count = $mealProvider->createMealsDataForDate(Carbon::parse($date));
            ActivityLogger::record($actor, 'meals.imported', new Meal(), after: compact('provider', 'date', 'count'));
            if ($notify) {
                $mealProvider->notifyAboutNewOrderPossibilities();
            }

            return $count;
        });
    }
}
