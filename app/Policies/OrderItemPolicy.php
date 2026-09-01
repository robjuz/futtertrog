<?php

namespace App\Policies;

use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Carbon;

class OrderItemPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_admin) {
            return true;
        }

        return null;
    }

    public function list(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the order.
     *
     * @return mixed
     */
    public function view(User $user, OrderItem $orderItem)
    {
        return $orderItem->user->is($user);
    }

    /**
     * Determine whether the user can create orders.
     *
     * @return mixed
     */
    public function create(User $user, Carbon $date)
    {
        return Carbon::parse($date)->isAfter(today());
    }

    /**
     * Determine whether the user can update the order.
     *
     * @return mixed
     */
    public function update(User $user, OrderItem $orderItem)
    {
        return $orderItem->user->is($user) && $orderItem->meal->date->isAfter(today());
    }

    /**
     * Determine whether the user can delete the order.
     *
     * @return mixed
     */
    public function delete(User $user, OrderItem $orderItem)
    {
        return $orderItem->user->is($user) && $orderItem->date->isAfter(today());
    }
}
