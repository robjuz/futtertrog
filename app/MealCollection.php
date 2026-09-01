<?php

namespace App;

use App\Models\Meal;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends Collection<int, Meal>
 */
class MealCollection extends Collection
{
    public function sortByPreferences(): self
    {
        return $this
            ->sortBy('id')
            ->sortByDesc(function (Meal $meal) {
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
