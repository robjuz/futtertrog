<?php

namespace App;

use Illuminate\Database\Eloquent\Collection;

class MealCollection extends Collection
{
    public function sortByPreferences()
    {
        return $this
            ->sortBy('id')
            ->sortByDesc(function ($meal) {
                if ($meal->is_hated) {
                    return -10 * $meal->id;
                }
                if ($meal->is_preferred) {
                    return 10 * $meal->id;
                }

                return -1 * $meal->id;
            });
    }
}
