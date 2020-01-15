<?php

namespace App\Listeners;

use App\Events\NewOrderPossibilities;
use App\Notifications\NewOrderPossibilities as NewOrderPossibilitiesNotification;
use App\User;
use Illuminate\Support\Facades\Notification;

class SendNewOrderPossibilitiesNotification
{
    /**
     * Handle the event.
     *
     * @param  NewOrderPossibility $event
     *
     * @return void
     */
    public function handle(NewOrderPossibilities $event)
    {
        $users = User::query()
                     ->where('settings->newOrderPossibilityNotification', '1')
                     ->get();

        Notification::send($users, new NewOrderPossibilitiesNotification($event->dates));
    }
}
