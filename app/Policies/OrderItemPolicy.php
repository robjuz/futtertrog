<?php

namespace App\Policies;

use App\OrderItem;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderItemPolicy
{
    use HandlesAuthorization;

    public function list(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the order.
     *
     * @param  \App\User      $user
     * @param  \App\OrderItem $orderItem
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
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the order.
     *
     * @param  \App\User      $user
     * @param  \App\OrderItem $orderItem
     *
     * @return mixed
     */
    public function update(User $user, OrderItem $orderItem)
    {
        return $orderItem->user->is($user);
    }

    /**
     * Determine whether the user can delete the order.
     *
     * @param  \App\User      $user
     * @param  \App\OrderItem $orderItem
     *
     * @return mixed
     */
    public function delete(User $user, OrderItem $orderItem)
    {
        return $orderItem->user->is($user);
    }
}
