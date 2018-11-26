<?php

namespace App\MealProviders;

use DiDom\Exceptions\InvalidSelectorException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;

class Basic extends AbstractMealProvider
{
    public function supportsAutoOrder(): bool
    {
        return false;
    }

    public function supportsOrderUpdate(): bool
    {
        return false;
    }

    /**
     * @param Carbon $date
     *
     * @throws InvalidSelectorException
     */
    public function getMealsDataForDate(Carbon $date): array
    {
        return [];
    }

    public function configureSchedule(Schedule $schedule): void
    {
        // TODO: Implement configureSchedule() method.
    }
}
