<?php

namespace App\Casts;

use App\MealProviders\AbstractMealProvider;
use App\MealProviders\Internal;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class MealProviderCast implements CastsAttributes
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
    public function get($model, string $key, $value, array $attributes)
    {

        return app()->make($value ?? basename(Internal::class));
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set($model, string $key, $value, array $attributes)
    {
        return $value instanceof AbstractMealProvider ? $value->getKey() : $value;
    }
}
