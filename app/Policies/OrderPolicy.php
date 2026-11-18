<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function list(User $user): bool
    {
        return $user->can('access-administration');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->can('access-administration');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->can('access-administration');
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->can('access-administration');
    }

    public function autoOrder(User $user, Order $order): bool
    {
        return $user->can('access-administration');
    }
}
