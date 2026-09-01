<?php

namespace App;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends Collection<int, Order>
 */
class OrderCollection extends Collection
{
    public function canBeAutoOrdered(): bool
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
