<?php

namespace App\Policies;

use App\User;
use App\Meal;
use Illuminate\Auth\Access\HandlesAuthorization;

class MealPolicy
{
    use HandlesAuthorization;


    public function list(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the meal.
     *
     * @param  \App\User  $user
     * @param  \App\Meal  $meal
     * @return mixed
     */
    public function view(User $user, Meal $meal)
    {
        //
    }

    /**
     * Determine whether the user can create meals.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
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
        return true;
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
        //
    }
}
