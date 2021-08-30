<?php

namespace App\Listeners;

use App\Events\OrderUpdated;
use App\MealProviders\HolzkeMealProvider;

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
    public function handle(OrderUpdated $event, HolzkeMealProvider $holzkeService)
    {
        $order = $event->order;

        if ($order->canBeAutoOrderedByHolzke() && $order->canBeUpdatedByHolzke()) {
            $holzkeService->updateOrder($event->orderItem);
        }
    }
}
