<?php

namespace App\Casts;

use App\UserSettings;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class UserSettingsCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  Model  $model
     * @param  string  $key
     * @param  mixed  $values
     * @param  array  $attributes
     * @return mixed
     */
    public function get(Model $model, string $key, mixed $values, array $attributes): UserSettings
    {
        $userSettings = new UserSettings;

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
     * @param  Model  $model
     * @param  string  $key
     * @param  UserSettings  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        return json_encode($value);
    }
}
