<?php

namespace App\Http\Controllers;

use App\Actions\Admin\SendNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class NotificationController extends Controller
{
    public function create()
    {
        Gate::authorize('send-notifications');

        $users = User::orderBy('name')->get();

        return view('notification.create', compact('users'));
    }

    public function store(Request $request)
    {
        Gate::authorize('send-notifications');

        $request->validate(
            [
                'user_id' => 'required|exists:users,id',
                'subject' => 'required|string|max:255',
                'body' => 'required',
            ]
        );

        app(SendNotification::class)->execute(
            $request->user(), $request->only(['user_id', 'subject', 'body'])
        );

        return redirect()->route('notifications.create')->with('success', __('Notification send'));
    }
}
