<?php

namespace App\Repositories;

use App\Meal;
use App\MealCollection;
use Illuminate\Support\Carbon;

class MealsRepository
{
    private $cache = [];

    /**
     * @param $date
     * @return MealCollection
     */
    public function forDate($date)
    {
        $date = Carbon::parse($date);

        if (empty($this->cache[$date->timestamp])) {
            $this->cache[$date->timestamp] = Meal::doesntHave('parent')->forDate($date)->get();
        }

        return $this->cache[$date->timestamp];
    }

    public function inFutureFrom($currentDate)
    {
        $currentDate = Carbon::parse($currentDate);

        $nextWeekMonday = $currentDate->clone()->addWeek()->startOfWeek();

        return Meal::doesntHave('parent')
            ->whereDate('date', '>=', $nextWeekMonday)
            ->get();
    }
}
