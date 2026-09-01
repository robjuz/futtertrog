<?php

namespace App;

use App\Casts\MealInfoCast;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @template TKey of array-key
 * @template TValue of mixed
 *
 * @implements Arrayable<TKey, TValue>
 */
class MealInfo implements Arrayable, Castable, JsonSerializable
{
    public ?float $calories = null;

    /** @var array<int, string> */
    public array $allergens = [];

    /** @var array<int, string> */
    public array $tags = [];

    public ?string $deposit = null;

    /**
     * Get the name of the caster class to use when casting from / to this cast target.
     *
     * @param  array<string, mixed>  $arguments
     */
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

    /** @return array<string, mixed> */
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
