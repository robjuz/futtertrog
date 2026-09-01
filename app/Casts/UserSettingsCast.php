<?php

namespace App\Casts;

use App\UserSettings;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<UserSettings, string>
 */
class UserSettingsCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @phpstan-return UserSettings<array-key, mixed>
     */
    public function get(Model $model, string $key, mixed $values, array $attributes): UserSettings
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
     * @phpstan-param UserSettings<array-key, mixed> $value
     * @phpstan-param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        return json_encode($value) ?: '{}';
    }
}
