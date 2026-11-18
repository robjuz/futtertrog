<?php

namespace App\Http\Requests;

use App\UserSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfilePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            UserSettings::NEW_ORDER_POSSIBILITY_NOTIFICATION => ['sometimes', 'boolean'],
            UserSettings::NO_ORDER_NOTIFICATION => ['sometimes', 'boolean'],
            UserSettings::NO_ORDER_FOR_NEXT_DAY_NOTIFICATION => ['sometimes', 'boolean'],
            UserSettings::NO_ORDER_FOR_NEXT_WEEK_NOTIFICATION => ['sometimes', 'boolean'],
            UserSettings::HIDE_DASHBOARD_MEAL_DESCRIPTION => ['sometimes', 'boolean'],
            UserSettings::HIDE_ORDERING_MEAL_DESCRIPTION => ['sometimes', 'boolean'],
            UserSettings::REDIRECT_TO_NEXT_DAY => ['sometimes', 'boolean'],
            UserSettings::LANGUAGE => ['sometimes', Rule::in(config('app.supported_locale'))],
            UserSettings::MEAL_PREFERENCES => ['sometimes', 'nullable', 'string', 'max:10000'],
            UserSettings::MEAL_AVERSION => ['sometimes', 'nullable', 'string', 'max:10000'],
            'id' => ['missing'], 'user_id' => ['missing'], 'is_admin' => ['missing'], 'roles' => ['missing'],
        ];
    }
}
