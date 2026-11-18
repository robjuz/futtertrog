<?php

namespace App\Policies;

use App\Models\Meal;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Carbon;

class OrderItemPolicy
{
    public function list(User $user): bool
    {
        return !$user->trashed();
    }

    public function view(User $user, OrderItem $orderItem): bool
    {
        return !$user->trashed() && $orderItem->user_id === $user->id;
    }

    public function create(User $user, Carbon|string $date): bool
    {
        return !$user->trashed() && Carbon::parse($date)->isAfter(today());
    }

    public function update(User $user, OrderItem $orderItem): bool
    {
        return $this->view($user, $orderItem) && $orderItem->date->isAfter(today());
    }

    public function delete(User $user, OrderItem $orderItem): bool
    {
        return $this->update($user, $orderItem);
    }

    public function administerAny(User $user): bool
    {
        return $user->can('access-administration');
    }

    public function administer(User $user, OrderItem $orderItem): bool
    {
        return $user->can('access-administration');
    }

    public function orderFor(User $user, Meal $meal, User $recipient): bool
    {
        return $user->can('access-administration') && !$recipient->trashed();
    }
}
