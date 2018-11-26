<?php

namespace App\ScheduledJobs;

use App\Models\User;
use App\Notifications\NoOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;

class NoOrderForNextWeekNotification
{
    public function __invoke()
    {
        $nextMonday = today()->addWeek()->startOfWeek();
        $nextSunday = today()->addWeek()->endOfWeek();
        $users = User::query()
            ->where(function(Builder $query) {
                $query->where('settings->noOrderForNextWeekNotification', '1')
                    ->orWhere('settings->noOrderForNextWeekNotification', true);
            })
            ->whereDoesntHave('orderItems.meal', function (Builder $q) use ($nextMonday, $nextSunday) {
                $q->whereBetween('date', [$nextMonday, $nextSunday]);
            })
            ->whereDoesntHave('disabledNotifications', function(Builder $q) use ($nextMonday, $nextSunday) {
                $q->whereBetween('date', [$nextMonday, $nextSunday]);
            })
            ->get();

        Notification::send($users, new NoOrder(__('Next week')));
    }
}
