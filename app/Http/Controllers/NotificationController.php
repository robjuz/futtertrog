<?php

namespace App\Http\Controllers;

use App\Notifications\CustomNotification;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class NotificationController extends Controller
{
    public function create()
    {
        $this->authorize('create', CustomNotification::class);

        $users = User::all('id', 'name');

        return view('notification.create', compact('users'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', CustomNotification::class);

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
