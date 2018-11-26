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
    public array $tags = [];
    public ?string $deposit = null;

    public static function castUsing(array $arguments): string
    {
        return MealInfoCast::class;
    }

    public function toArray()
    {
        return [
            'calories' => $this->calories,
            'allergens' => $this->allergens,
            'tags' => $this->tags,
            'deposit' => $this->deposit,
        ];
    }

    public function jsonSerialize(): array
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
