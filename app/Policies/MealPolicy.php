<?php

namespace App\Policies;

use App\User;
use App\Meal;
use Illuminate\Auth\Access\HandlesAuthorization;

class MealPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the meal.
     *
     * @param  \App\User  $user
     * @param  \App\Meal  $meal
     * @return mixed
     */
    public function view(User $user, Meal $meal)
    {
        return true;
    }

    /**
     * Determine whether the user can create meals.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->is_admin;
    }

    /**
     * Determine whether the user can update the meal.
     *
     * @param  \App\User  $user
     * @param  \App\Meal  $meal
     * @return mixed
     */
    public function update(User $user, Meal $meal)
    {
        return $user->is_admin;
    }

    /**
     * Determine whether the user can delete the meal.
     *
     * @param  \App\User  $user
     * @param  \App\Meal  $meal
     * @return mixed
     */
    public function delete(User $user, Meal $meal)
    {
        return $user->is_admin;
    }

    /**
     * Determine whether the user can restore the meal.
     *
     * @param  \App\User  $user
     * @param  \App\Meal  $meal
     * @return mixed
     */
    public function restore(User $user, Meal $meal)
    {
        return $user->is_admin;
    }

    /**
     * Determine whether the user can permanently delete the meal.
     *
     * @param  \App\User  $user
     * @param  \App\Meal  $meal
     * @return mixed
     */
    public function forceDelete(User $user, Meal $meal)
    {
        return $user->is_admin;
    }

    public function order(User $user, Meal $meal)
    {
        return $meal->date > today();
    }
}
