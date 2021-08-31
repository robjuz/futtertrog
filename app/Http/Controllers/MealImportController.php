<?php

namespace App\Http\Controllers;

use App\Events\NewOrderPossibility;
use App\Meal;
use App\MealProviders\CallAPizzaMealProvider;
use App\Services\MealService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class MealImportController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function __invoke(Request $request, MealService $mealService)
    {
        $this->authorize('create', Meal::class);

        $request->validate([
            'date' => 'required|date',
            'provider' => ['required', Rule::in(array_keys(app('mealProviders')))],
        ]);

        $date = Carbon::parse($request->date);

        $mealService->setProvider(app($request->provider));
        $mealService->getMealsForDate($date);

        if ($request->has('notify')) {
            $mealService->notify();
        }

        return redirect()->route('meals.create')->with('success', __('Saved'));
    }
}
