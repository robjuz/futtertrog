<?php

namespace App;

use Illuminate\Database\Eloquent\Collection;

class OrderCollection extends Collection
{
    public function canBeAutoOrdered()
    {
        if ($this->isEmpty()) {
            return false;
        }

        /** @var Order $order */
        foreach ($this->items as $order) {
            if (! $order->canBeAutoOrdered()) {
                return false;
            }
        }

        return true;
    }
}
