<?php

namespace App;

use Illuminate\Database\Eloquent\Collection;

class OrderCollection extends Collection
{
    public function canBeAutoOrderedByHolzke()
    {
        if ($this->isEmpty()) {
            return false;
        }

        /** @var Order $order */
        foreach ($this->items as $order) {
            if (! $order->canBeAutoOrderedByHolzke()) {
                return false;
            }
        }

        return true;
    }
}
