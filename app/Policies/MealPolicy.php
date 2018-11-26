<?php

namespace App\Policies;

use App\Models\Meal;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MealPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        if ($user->is_admin) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the meal.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Meal  $meal
     * @return mixed
     */
    public function view(User $user, Meal $meal)
    {
        //
    }

    /**
     * Determine whether the user can create meals.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the meal.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Meal  $meal
     * @return mixed
     */
    public function update(User $user, Meal $meal)
    {
        //
    }

    /**
     * Determine whether the user can delete the meal.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Meal  $meal
     * @return mixed
     */
    public function delete(User $user, Meal $meal)
    {
        //
    }
}
