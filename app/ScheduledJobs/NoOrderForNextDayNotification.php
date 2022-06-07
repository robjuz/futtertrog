<?php

namespace App\ScheduledJobs;

use App\Notifications\NoOrder;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;

class NoOrderForNextDayNotification
{
    public function __invoke()
    {
        $nextDay = today()->addWeekday();
        $users = User::query()
            ->where(function(Builder $query) {
                $query->where('settings->noOrderForNextDayNotification', '1')
                    ->orWhere('settings->noOrderForNextDayNotification', true);
            })
            ->whereDoesntHave('orderItems.order', function (Builder $q) use ($nextDay) {
                return $q->whereDate('date', $nextDay);
            })
            ->get();

        Notification::send($users, new NoOrder(__('calendar.'.$nextDay->englishDayOfWeek)));
    }
}
