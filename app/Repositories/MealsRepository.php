<?php

namespace App\Repositories;

use App\Meal;
use Illuminate\Support\Carbon;

class MealsRepository
{
    private $cache = [];

    /**
     * @param $date
     * @return \App\MealCollection
     */
    public function forDate($date)
    {
        $date = Carbon::parse($date);

        if (empty($this->cache[$date->timestamp])) {
            $this->cache[$date->timestamp] = Meal::doesntHave('parent')->forDate($date)->get();
        }

        return $this->cache[$date->timestamp];
    }
}
