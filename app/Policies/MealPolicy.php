<?php

namespace App\Policies;

use App\Meal;
use App\User;
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
        //
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
        //
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
