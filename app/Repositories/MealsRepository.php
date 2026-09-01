<?php

namespace App\Repositories;

use App\MealCollection;
use App\Models\Meal;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class MealsRepository
{
    /** @var array<int, Collection<int, Meal>> */
    private array $cache = [];

    /**
     * @return Collection<int, Meal>
     */
    public function forDate(Carbon $date): Collection
    {
        $date = Carbon::parse($date);
        $timestamp = (int) $date->timestamp;

        if (empty($this->cache[$timestamp])) {
            $this->cache[$timestamp] = Meal::doesntHave('parent')->forDate($date)->get();
        }

        /** @var Collection<int, Meal> $result */
        $result = $this->cache[$timestamp];

        return $result;
    }

    /**
     * @return Collection<int, Meal>
     */
    public function inFutureFrom(Carbon $currentDate): Collection
    {
        $currentDate = Carbon::parse($currentDate);

        $nextWeekMonday = $currentDate->clone()->addWeek()->startOfWeek();

        /** @var Collection<int, Meal> */
        return Meal::doesntHave('parent')
            ->whereDate('date', '>=', $nextWeekMonday)
            ->get();
    }
}
