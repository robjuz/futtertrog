<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
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
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $settings = $request->validate([
            User::SETTING_NEW_ORDER_POSSIBILITY_NOTIFICATION => ['required', 'boolean'],
            User::SETTING_NO_ORDER_NOTIFICATION => ['required', 'boolean'],
            User::SETTING_NO_ORDER_FOR_NEXT_DAY_NOTIFICATION => ['required', 'boolean'],
            User::SETTING_MEAL_PREFERENCES => ['nullable', 'string'],
            User::SETTING_MEAL_AVERSION => ['nullable', 'string'],
            User::SETTING_HIDE_DASHBOARD_MEAL_DESCRIPTION => ['required', 'boolean'],
            User::SETTING_HIDE_ORDERING_MEAL_DESCRIPTION => ['required', 'boolean'],
            User::SETTING_DARK_MODE => ['required', 'boolean'],
            User::SETTING_LANGUAGE => ['required', Rule::in(config('app.supported_locale'))],
            User::SETTING_MEALS_LIST_TYPE => ['required', Rule::in(['list', 'two-columns', 'grid'])],
        ]);

        $user = $request->user();
        $user->settings = $settings;
        $user->save();

        if ($request->wantsJson()) {
            return response()->json($settings);
        }

        return back()->with('success', __('Saved'));
    }
}
