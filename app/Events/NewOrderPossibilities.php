<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Traversable;

class NewOrderPossibilities
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Carbon[]
     */
    public $dates;

    /**
     * Create a new event instance.
     *
     * @param  Traversable  $dates
     */
    public function __construct(Traversable $dates)
    {
        $this->dates = $dates;
    }

//    /**
//     * Get the channels the event should broadcast on.
//     *
//     * @return \Illuminate\Broadcasting\Channel|array
//     */
//    public function broadcastOn()
//    {
//        return new PrivateChannel('channel-name');
//    }
}
