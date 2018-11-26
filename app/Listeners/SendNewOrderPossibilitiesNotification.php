<?php

namespace App\Listeners;

use App\Events\NewOrderPossibilities;
use App\Models\User;
use App\Notifications\NewOrderPossibilities as NewOrderPossibilitiesNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;

class SendNewOrderPossibilitiesNotification
{
    /**
     * Handle the event.
     *
     * @param NewOrderPossibilities $event
     * @return void
     */
    public function handle(NewOrderPossibilities $event)
    {
        $users = User::query()
            ->where(function (Builder $query) {
                $query->where('settings->newOrderPossibilityNotification', '1')
                    ->orWhere('settings->newOrderPossibilityNotification', true);
            })
            ->get();

        Notification::send($users, new NewOrderPossibilitiesNotification($event->dates));
    }
}
