<?php

namespace App;

use App\Casts\MealInfoCast;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class MealInfo implements Castable, Arrayable, JsonSerializable
{
    public ?float $calories = null;
    public array $allergens = [];

    public static function castUsing()
    {
        return MealInfoCast::class;
    }

    public function toArray()
    {
        return [
            'calories' => $this->calories,
            'allergens' => $this->allergens,
        ];
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function isEmpty(): bool
    {
        return empty(array_filter($this->toArray()));
    }

    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }
}
