<?php

namespace App\Listeners;

use App\Events\OrderUpdated;
use App\Models\User;
use App\Notifications\OrderReopenedNotification;
use Illuminate\Support\Facades\Notification;

class SendOrderReopenedNotification
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
     * @param  OrderUpdated  $event
     * @return void
     */
    public function handle(OrderUpdated $event)
    {
        if ($event->order->wasReopened()) {
            Notification::send(
                User::where('is_admin', true)->get(),
                new OrderReopenedNotification($event->order, $event->user, $event->orderItem->meal)
            );
        }
    }
}
