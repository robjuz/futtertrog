<?php

namespace App\MealProviders;

use App\MealInfo;
use App\Order;
use App\OrderItem;
use App\Services\MealService;
use DiDom\Document;
use DiDom\Element;
use DiDom\Exceptions\InvalidSelectorException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Ixudra\Curl\Facades\Curl;
use Symfony\Component\HttpFoundation\Response;

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
