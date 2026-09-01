<?php

namespace App\Casts;

use App\MealInfo;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @implements CastsAttributes<MealInfo, array<string, mixed>>
 */
class MealInfoCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  Model  $model
     * @param  string  $key
     * @param  mixed  $values
     * @param  array<string, mixed>  $attributes
     * @return mixed
     */
    public function get($model, $key, $values, $attributes)
    {
        $mealInfo = new MealInfo();

        if (! $values) {
            return $mealInfo;
        }

        foreach (json_decode($values) as $property => $value) {
            if (property_exists($mealInfo, $property)) {
                $mealInfo->$property = $value;
            }
        }

        return $mealInfo;
    }

    /**
     * Prepare the given value for storage.
     *
     * @phpstan-param MealInfo<array-key, mixed> $value
     * @phpstan-param array<string, mixed> $attributes
     *
     * @return mixed
     */
    public function set($model, $key, $value, $attributes)
    {
        return [
            'info' => json_encode($value),
        ];
    }
}
