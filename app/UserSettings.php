<?php

namespace App;

use App\Casts\MealInfoCast;
use App\Casts\UserSettingsCast;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class UserSettings implements Castable, Arrayable, JsonSerializable
{
    const NEW_ORDER_POSSIBILITY_NOTIFICATION = 'newOrderPossibilityNotification';
    const NO_ORDER_NOTIFICATION = 'noOrderNotification';
    const NO_ORDER_FOR_NEXT_DAY_NOTIFICATION = 'noOrderForNextDayNotification';
    const NO_ORDER_FOR_NEXT_WEEK_NOTIFICATION = 'noOrderForNextWeekNotification';
    const MEAL_PREFERENCES = 'mealPreferences';
    const MEAL_AVERSION = 'mealAversion';
    const HIDE_DASHBOARD_MEAL_DESCRIPTION = 'hideDashboardMealDescription';
    const HIDE_ORDERING_MEAL_DESCRIPTION = 'hideOrderingMealDescription';
    const LANGUAGE = 'language';

    public bool $newOrderPossibilityNotification = false;
    public bool $noOrderNotification = false;
    public bool $noOrderForNextDayNotification = false;
    public bool $noOrderForNextWeekNotification = false;
    public bool $hideDashboardMealDescription = false;
    public bool $hideOrderingMealDescription = false;
    public ?string $mealPreferences = '';
    public ?string $mealAversion = '';
    public ?string $language = null;

    public static function castUsing(array $arguments): string
    {
        return UserSettingsCast::class;
    }

    public function toArray()
    {
        return [
            self::NEW_ORDER_POSSIBILITY_NOTIFICATION => $this->newOrderPossibilityNotification,
            self::NO_ORDER_NOTIFICATION => $this->noOrderNotification,
            self::NO_ORDER_FOR_NEXT_DAY_NOTIFICATION => $this->noOrderForNextDayNotification,
            self::NO_ORDER_FOR_NEXT_WEEK_NOTIFICATION => $this->noOrderForNextWeekNotification,
            self::MEAL_PREFERENCES => $this->mealPreferences,
            self::MEAL_AVERSION => $this->mealAversion,
            self::HIDE_DASHBOARD_MEAL_DESCRIPTION => $this->hideDashboardMealDescription,
            self::HIDE_ORDERING_MEAL_DESCRIPTION => $this->hideOrderingMealDescription,
            self::LANGUAGE => $this->language,
        ];
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
