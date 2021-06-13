<?php

namespace App\Events;

use App\Meal;
use App\Order;
use App\OrderItem;
use App\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderUpdated
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
     * @var OrderItem
     */
    public $orderItem;

    /**
     * Create a new event instance.
     *
     * @param Order $order
     * @param User $user
     * @param OrderItem $orderItem
     */
    public function __construct(Order $order, User $user, OrderItem $orderItem)
    {
        $this->order = $order;
        $this->user = $user;
        $this->orderItem = $orderItem;
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
