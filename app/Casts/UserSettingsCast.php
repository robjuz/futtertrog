<?php

namespace App\Casts;

use App\MealInfo;
use App\UserSettings;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;

class UserSettingsCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $values
     * @param  array  $attributes
     * @return mixed
     */
    public function get($model, $key, $values, $attributes)
    {
        $userSettings = new UserSettings();

        if (! $values) {
            return $userSettings;
        }

        foreach (json_decode($values) as $property => $value) {
            if (property_exists($userSettings, $property)) {
                $userSettings->$property = $value;
            }
        }

        return $userSettings;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  \App\UserSettings  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set($model, $key, $value, $attributes)
    {
        return json_encode($value);
    }
}
