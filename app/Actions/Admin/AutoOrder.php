<?php

namespace App\Actions\Admin;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class AutoOrder
{
    /** Do not wrap in a transaction: provider uncertainty must survive a caller rollback. */
    public function execute(User $actor, Order $order): Order
    {
        $actor = ActivityLogger::authorize($actor);
        Gate::forUser($actor)->authorize('autoOrder', $order);

        return $order->autoOrder($actor);
    }
}
