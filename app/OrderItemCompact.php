<?php

namespace App;

use App\Models\Meal;
use App\Models\User;

class OrderItemCompact
{
    /**
     * @var Meal
     */
    public $meal;

    /** @var iterable<User> */
    public iterable $users;

    /**
     * @var int
     */
    public $quantity;

    /**
     * @param  iterable<User>  $users
     */
    public function __construct(Meal $meal, iterable $users, int $quantity)
    {
        $this->meal = $meal;
        $this->users = collect($users);
        $this->quantity = $quantity;
    }
}
