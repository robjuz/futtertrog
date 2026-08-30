<?php

namespace App\Casts;

use App\MealProviders\AbstractMealProvider;
use App\MealProviders\Internal;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class MealProviderCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  Model  $model
     * @param  mixed  $value
     * @return mixed
     */
    public function get($model, string $key, $value, array $attributes)
    {

        return app()->make($value ?? basename(Internal::class));
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  Model  $model
     * @param  mixed  $value
     * @return mixed
     */
    public function set($model, string $key, $value, array $attributes)
    {
        return $value instanceof AbstractMealProvider ? $value->getKey() : $value;
    }
}
