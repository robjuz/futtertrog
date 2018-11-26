<?php

namespace App;

use App\Models\Meal;

class OrderItemCompact
{
    /**
     * @var \App\Models\Meal
     */
    public $meal;

    /**
     * @var iterable|\App\Models\User[]
     */
    public $users;

    /**
     * @var int
     */
    public $quantity;

    public function __construct(Meal $meal, iterable $users, int $quantity)
    {
        $this->meal = $meal;
        $this->users = collect($users);
        $this->quantity = $quantity;
    }
}
