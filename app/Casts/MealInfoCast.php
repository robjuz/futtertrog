<?php

namespace App\Casts;

use App\MealInfo;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;

class MealInfoCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function get($model, $key, $value, $attributes)
    {
        $mealInfo = new MealInfo();

        $value = json_decode($value);

        $mealInfo->calories = $value->calories;

        return $mealInfo;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  \App\MealInfo  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set($model, $key, $value, $attributes)
    {
        if (! $value instanceof MealInfo) {
            throw new InvalidArgumentException('The given value is not an MealInfo instance.');
        }

        return [
            'info' => json_encode($value),
        ];
    }
}
