<?php

namespace App\Actions\Admin;

use App\Models\User;
use App\Notifications\CustomNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

class SendNotification
{
    /** @param array{user_id: int|array<int>, subject: string, body: string} $data */
    public function execute(User $actor, array $data): void
    {
        $actor = ActivityLogger::authorize($actor);
        Gate::forUser($actor)->authorize('send-notifications');
        $data = Validator::make($data, [
            'user_id' => 'required|exists:users,id', 'subject' => 'required|string|max:255', 'body' => 'required',
        ])->validate();
        DB::transaction(function () use ($actor, $data): void {
            $recipients = User::findMany($data['user_id']);
            Notification::send($recipients, new CustomNotification($data['subject'], $data['body']));
            ActivityLogger::record($actor, 'notification.sent', $actor, after: ['recipient_ids' => $recipients->modelKeys()]);
        });
    }
}
