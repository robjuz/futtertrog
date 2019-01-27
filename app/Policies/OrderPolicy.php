<?php

namespace App\Policies;

use App\User;
use App\OrderItem;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
{
    use HandlesAuthorization;


    public function list(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the order.
     *
     * @param  \App\User      $user
     * @param  \App\OrderItem $order
     *
     * @return mixed
     */
    public function view(User $user, OrderItem $order)
    {
        //
    }

    /**
     * Determine whether the user can create orders.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the order.
     *
     * @param  \App\User      $user
     * @param  \App\OrderItem $order
     *
     * @return mixed
     */
    public function update(User $user, OrderItem $order)
    {
        //
    }

    /**
     * Determine whether the user can delete the order.
     *
     * @param  \App\User      $user
     * @param  \App\OrderItem $order
     *
     * @return mixed
     */
    public function delete(User $user, OrderItem $order)
    {
        //
    }

    /**
     * Determine whether the user can restore the order.
     *
     * @param  \App\User  $user
     * @param  \App\OrderItem  $order
     *
     * @return mixed
     */
    public function restore(User $user, OrderItem $order)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the order.
     *
     * @param  \App\User      $user
     * @param  \App\OrderItem $order
     *
     * @return mixed
     */
    public function forceDelete(User $user, OrderItem $order)
    {
        //
    }
}
