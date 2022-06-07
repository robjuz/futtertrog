<?php

namespace App\Http\Controllers;

use App\User;
use App\UserSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $settings = $request->user()->settings ?? [];

        if ($request->wantsJson()) {
            return response()->json($settings);
        }

        return view('settings.index', compact('settings'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
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
