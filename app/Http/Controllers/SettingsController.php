<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\UserSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View|JsonResponse
    {
        $settings = $request->user()->settings ?? [];

        if ($request->wantsJson()) {
            return response()->json($settings);
        }

        return view('settings.index', compact('settings'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $settings = $request->validate([
            UserSettings::NEW_ORDER_POSSIBILITY_NOTIFICATION => ['required', 'boolean'],
            UserSettings::NO_ORDER_NOTIFICATION => ['required', 'boolean'],
            UserSettings::NO_ORDER_FOR_NEXT_DAY_NOTIFICATION => ['required', 'boolean'],
            UserSettings::NO_ORDER_FOR_NEXT_WEEK_NOTIFICATION => ['required', 'boolean'],
            UserSettings::MEAL_PREFERENCES => ['nullable', 'string'],
            UserSettings::MEAL_AVERSION => ['nullable', 'string'],
            UserSettings::HIDE_DASHBOARD_MEAL_DESCRIPTION => ['required', 'boolean'],
            UserSettings::HIDE_ORDERING_MEAL_DESCRIPTION => ['required', 'boolean'],
            UserSettings::LANGUAGE => ['required', Rule::in(config('app.supported_locale'))],
            UserSettings::REDIRECT_TO_NEXT_DAY => ['required', 'boolean'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->settings = $settings;
        $user->save();

        if ($request->wantsJson()) {
            return response()->json($settings);
        }

        return back()->with('success', __('Saved'));
    }
}
