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
                    return -1;
                }
                if ($meal->is_preferred) {
                    return 1;
                }

                return 0;
            });
    }
}
