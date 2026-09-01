<?php

namespace App\Rules;

use App\Models\Meal;
use Illuminate\Contracts\Validation\Rule;

class MealWithoutVariants implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $mealId
     */
    public function passes($attribute, $mealId): bool
    {
        /** @var Meal $meal */
        $meal = Meal::findOrFail($mealId);

        return $meal->variants()->doesntExist();
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return __('This meal has variants. You need to order a variant');
    }
}
