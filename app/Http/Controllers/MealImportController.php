<?php

namespace App\Http\Controllers;

use App\Events\NewOrderPossibility;
use App\Meal;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class MealImportController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $this->authorize('create', Meal::class);

        $request->validate([
            'date' => 'required|date',
            'provider' => ['required', Rule::in(Meal::$providers)],
        ]);

        $date = Carbon::parse($request->date);
        $provider = $request->provider;

        $providerService = app($provider.'_service');
        $meals = $providerService->getMealsForDate($date);

        foreach ($meals as $mealElement) {
            Meal::updateOrCreate(
                [
                    'title' => $mealElement['title'],
                    'date_from' => $date->toDateString(),
                    'date_to' => $date->toDateString(),
                    'provider' => $provider,
                ],
                [
                    'description' => $mealElement['description'],
                    'price' => $mealElement['price'],
                    'image' => $mealElement['image'] ?? null,
                ]
            );
        }

        if ($request->has('notify')) {
            event(new NewOrderPossibility($request->date));
        }

        return redirect()->route('meals.create')->with('success', __('Saved'));
    }
}
