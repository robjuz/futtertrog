<?php

namespace App\Listeners;

use App\Events\NewOrderPossibility;
use App\Models\User;
use App\Notifications\NewOrderPossibility as NewOrderPossibilityNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;

class SendNewOrderPossibilityNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param NewOrderPossibility $event
     * @return void
     */
    public function handle(NewOrderPossibility $event)
    {
        $users = User::query()
            ->where(function (Builder $query) {
                $query->where('settings->newOrderPossibilityNotification', '1')
                    ->orWhere('settings->newOrderPossibilityNotification', true);
            })
            ->get();

        Notification::send($users, new NewOrderPossibilityNotification($event->date));
    }
}
