<?php

namespace App\ScheduledJobs;

use App\Notifications\NoOrder;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;

class NoOrderForTodayNotification
{
    public function __invoke()
    {
        $users = User::query()
            ->where('settings->noOrderNotification', '1')
            ->whereDoesntHave('orderItems.order', function (Builder $q) {
                return $q->whereDate('date', today());
            })
            ->get();

        Notification::send($users, new NoOrder(today()));
    }
}
