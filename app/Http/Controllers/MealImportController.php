<?php

namespace App\Http\Controllers;

use App\MealProviders\AbstractMealProvider;
use App\Models\Meal;
use App\Services\MealService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class MealImportController extends Controller
{
    public function __invoke(Request $request, MealService $mealService): RedirectResponse
    {
        Gate::authorize('create', Meal::class);

        $request->validate([
            'date' => 'required|date',
            'provider' => ['required', Rule::in(array_keys(app('mealProviders')))],
        ]);

        $date = Carbon::parse($request->date);

        /** @var AbstractMealProvider $mealProvider */
        $mealProvider = app()->make($request->provider);

        $mealProvider->createMealsDataForDate($date);

        if ($request->has('notify')) {
            $mealProvider->notifyAboutNewOrderPossibilities();
        }

        return redirect()->route('meals.create')->with('success', __('Saved'));
    }
}
