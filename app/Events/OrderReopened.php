<?php

namespace App\Events;

use App\Meal;
use App\Order;
use App\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

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
     * @param User      $user
     * @param Meal      $meal
     */
    public function __construct(Order $order, User $user, Meal $meal)
    {

        $this->order = $order;
        $this->user = $user;
        $this->meal = $meal;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
