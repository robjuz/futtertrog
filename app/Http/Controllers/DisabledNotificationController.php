<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DisabledNotificationController extends Controller
{

    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'date' => 'date'
            ]
        );
        /** @var User $user */
        $user = $request->user();

        $orderDisabledNotification = $user->disabledNotifications()->create($validated);

        if ($request->wantsJson()) {
            return response($orderDisabledNotification, Response::HTTP_CREATED);
        }

        return back()->with('success', __('Success'));
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate(
            [
                'date' => 'date'
            ]
        );
        /** @var User $user */
        $user = $request->user();

        $user->disabledNotifications()->whereDate('date', $request->input('date'))->delete();

        if ($request->wantsJson()) {
            return response(null, Response::HTTP_NO_CONTENT);
        }

        return back()->with('success', __('Success'));
    }
}
