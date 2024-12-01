<?php

namespace App\MealProviders;

use App\MealProviders\Interfaces\HasWeeklyOrders;
use DiDom\Exceptions\InvalidSelectorException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;

class Internal extends AbstractMealProvider implements HasWeeklyOrders
{
    public function supportsAutoOrder(): bool
    {
        return false;
    }

    public function supportsOrderUpdate(): bool
    {
        return false;
    }

    public function getMealsDataForDate(Carbon $date): array
    {
        return [];
    }

    public function configureSchedule(Schedule $schedule): void
    {
        return;
    }
}
