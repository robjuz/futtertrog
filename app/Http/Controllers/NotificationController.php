<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\CustomNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

class NotificationController extends Controller
{
    public function create()
    {
        Gate::authorize('create', CustomNotification::class);

        $users = User::orderBy('name')->get();

        return view('notification.create', compact('users'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', CustomNotification::class);

        $request->validate(
            [
                'user_id' => 'required|exists:users,id',
                'subject' => 'required|string|max:255',
                'body' => 'required',
            ]
        );

        Notification::send(
            User::findMany($request->input('user_id')),
            new CustomNotification($request->input('subject'), $request->input('body'))
        );

        return redirect()->route('notifications.create')->with('success', __('Notification send'));
    }
}
