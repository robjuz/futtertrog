<?php

namespace App\Events;

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
     * Create a new event instance.
     *
     * @param  Order  $order
     * @param  User  $user
     * @param  OrderItem  $orderItem
     */
    public function __construct(public Order $order, public User $user, public OrderItem $orderItem)
    {
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
