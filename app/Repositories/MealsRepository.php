<?php

namespace App\Repositories;

use App\MealCollection;
use App\Models\Meal;
use Illuminate\Support\Carbon;

class MealsRepository
{
    /** @var array<int, MealCollection> */
    private array $cache = [];

    /**
     * @return MealCollection
     */
    public function forDate(Carbon $date)
    {
        $date = Carbon::parse($date);
        $timestamp = (int) $date->timestamp;

        if (empty($this->cache[$timestamp])) {
            $this->cache[$timestamp] = Meal::doesntHave('parent')->forDate($date)->get();
        }

        return $this->cache[$timestamp];
    }

    public function inFutureFrom(Carbon $currentDate): MealCollection
    {
        $currentDate = Carbon::parse($currentDate);

        $nextWeekMonday = $currentDate->clone()->addWeek()->startOfWeek();

        return Meal::doesntHave('parent')
            ->whereDate('date', '>=', $nextWeekMonday)
            ->get();
    }
}
