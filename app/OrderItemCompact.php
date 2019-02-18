<?php

namespace App;

class OrderItemCompact
{
    /**
     * @var \App\Meal
     */
    public $meal;

    /**
     * @var iterable|\App\User[]
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