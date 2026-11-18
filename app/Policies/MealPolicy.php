<?php

namespace App\Policies;

use App\Models\Meal;
use App\Models\User;

class MealPolicy
{
    public function view(User $user, Meal $meal): bool
    {
        return !$user->trashed();
    }

    public function create(User $user): bool
    {
        return $user->can('access-administration');
    }

    public function update(User $user, Meal $meal): bool
    {
        return $user->can('access-administration');
    }

    public function delete(User $user, Meal $meal): bool
    {
        return $user->can('access-administration');
    }
}
