<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
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
            'noOrderNotification' => ['required', 'boolean'],
            'noOrderForNextDayNotification' => ['required', 'boolean'],
//             TODO: add in v2.2
//            'includes' => ['nullable', 'string'],
//            'excludes' => ['nullable', 'string']
        ]);

        $user = $request->user();
        $user->settings = $settings;
        $user->save();

        if ($request->wantsJson()) {
            return response()->json($settings);
        }

        return back();
    }
}
