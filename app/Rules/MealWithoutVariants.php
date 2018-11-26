<?php

namespace App\Rules;

use App\Models\Meal;
use Illuminate\Contracts\Validation\Rule;

class MealWithoutVariants implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $mealId
     * @return bool
     */
    public function passes($attribute, $mealId)
    {
        return Meal::findOrNew($mealId)->variants()->doesntExist();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('This meal has variants. You need to order a variant');
    }
}
