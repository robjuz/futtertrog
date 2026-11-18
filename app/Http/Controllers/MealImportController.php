<?php

namespace App\Http\Controllers;

use App\Actions\Admin\ImportMeals;
use App\Models\Meal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class MealImportController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        Gate::authorize('create', Meal::class);

        $request->validate([
            'date' => 'required|date',
            'provider' => ['required', Rule::in(array_keys(app('mealProviders')))],
        ]);

        app(ImportMeals::class)->execute(
            $request->user(), $request->input('provider'), $request->input('date'), $request->has('notify')
        );

        return redirect()->route('meals.create')->with('success', __('Saved'));
    }
}
