<?php

namespace App\Actions\Admin;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Orders
{
    /** @param array{status?: string, payed_at?: ?string, user_id?: ?int} $data */
    public function update(User $actor, Order $order, array $data): Order
    {
        return DB::transaction(function () use ($actor, $order, $data): Order {
            $actor = ActivityLogger::authorize($actor);
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('update', $order);
            $data = Validator::make($data, [
                'status' => ['sometimes', 'string', Rule::in(Order::$statuses)],
                'payed_at' => ['date', 'sometimes', 'nullable'], 'user_id' => 'sometimes|nullable',
            ])->validate();
            $before = $order->getAttributes();
            $order->update($data);
            ActivityLogger::record($actor, 'order.updated', $order, $before, $order->getAttributes());

            return $order;
        });
    }

    public function delete(User $actor, Order $order): void
    {
        DB::transaction(function () use ($actor, $order): void {
            $actor = ActivityLogger::authorize($actor);
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('delete', $order);
            $before = $order->getAttributes();
            $order->delete();
            ActivityLogger::record($actor, 'order.deleted', $order, $before);
        });
    }
}
