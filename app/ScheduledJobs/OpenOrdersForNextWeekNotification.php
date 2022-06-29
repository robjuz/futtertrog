<?php

namespace App\ScheduledJobs;

use App\Notifications\OpenOrders;
use App\Order;
use App\OrderItem;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;

class OpenOrdersForNextWeekNotification
{
    public function __invoke()
    {
        $nextMonday = today()->addWeek()->startOfWeek();
        $nextSunday = today()->addWeek()->endOfWeek();

        if (OrderItem::query()->whereRelation('order', 'status', Order::STATUS_OPEN)
            ->whereHas('meal', function (Builder $query) use ($nextMonday, $nextSunday) {
                $query->whereBetween('date', [$nextMonday, $nextSunday]);
            })
            ->exists()
        ) {
            $users = User::whereIsAdmin(true)->get();

            Notification::send($users, new OpenOrders(__('Next week')));
        }
    }
}
