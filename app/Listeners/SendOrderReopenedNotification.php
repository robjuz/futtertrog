<?php

namespace App\Listeners;

use App\Events\OrderUpdated;
use App\Notifications\OrderReopenedNotification;
use App\User;
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
        if ($event->order->isOpen) {
            Notification::send(User::where('is_admin', true)->get(), new OrderReopenedNotification($event->order, $event->user, $event->orderItem->meal));
        }
    }
}
