<?php

namespace App\Listeners;

use App\Events\OrderUpdated;
use App\Services\HolzkeService;

class AutoOrder
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
    public function handle(OrderUpdated $event, HolzkeService $holzkeService)
    {
        $order = $event->order;

        if ($order->canBeAutoOrderedByHolzke() && $order->canBeUpdatedByHolzke()) {
            $holzkeService->updateOrder($event->orderItem);
        }
    }
}
