<?php

namespace App\Policies;

use App\Models\Deposit;
use App\Models\User;

class DepositPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('access-administration');
    }

    public function create(User $user): bool
    {
        return $user->can('access-administration');
    }

    public function update(User $user, Deposit $deposit): bool
    {
        return $user->can('access-administration');
    }

    public function delete(User $user, Deposit $deposit): bool
    {
        return $user->can('access-administration');
    }

    public function transfer(User $user, User $source, User $target): bool
    {
        return $user->can('access-administration') && !$source->trashed() && !$target->trashed();
    }
}
