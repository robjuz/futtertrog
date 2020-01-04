<?php

namespace App\ScheduledJobs;

use App\Notifications\OpenOrders;
use App\Order;
use App\User;
use Illuminate\Support\Facades\Notification;

class OpenOrdersForNextWeekNotification
{
    public function __invoke()
    {
        $nextMonday = today()->addWeek()->startOfWeek();
        $nextSunday = today()->addWeek()->endOfWeek();

        if (Order::whereStatus(Order::STATUS_OPEN)
             ->whereBetween('date', [$nextMonday, $nextSunday])
             ->exists()
        ) {
            $users = User::whereIsAdmin(true)->get();

            Notification::send($users, new OpenOrders(__('Next week')));
        }
    }
}
