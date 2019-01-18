<?php

namespace App\Listeners;

use App\Events\OrderReopened;
use App\Notifications\OrderReopenedNotification;
use App\User;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
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
     * @param  OrderReopened  $event
     * @return void
     */
    public function handle(OrderReopened $event)
    {
        Notification::send(User::where('is_admin', true)->get(), new OrderReopenedNotification($event->order, $event->user, $event->meal));
    }
}
