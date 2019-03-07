<?php

namespace App\Events;

use App\Meal;
use App\Order;
use App\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderReopened
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    /**
     * @var Order
     */
    public $order;
    /**
     * @var User
     */
    public $user;
    /**
     * @var Meal
     */
    public $meal;

    /**
     * Create a new event instance.
     *
     * @param Order $order
     * @param User $user
     * @param Meal $meal
     */
    public function __construct(Order $order, User $user, Meal $meal)
    {
        $this->order = $order;
        $this->user = $user;
        $this->meal = $meal;
    }

//    /*
//     * Get the channels the event should broadcast on.
//     *
//     * @return \Illuminate\Broadcasting\Channel|array
//     */
//    public function broadcastOn()
//    {
//        return new PrivateChannel('channel-name');
//    }
}
