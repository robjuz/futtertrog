<?php

namespace App\Console;

use App\Notifications\NoOrder;
use App\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Notification;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();

        if (config('services.holzke.schedule')) {
            $schedule->command('import:holzke')->dailyAt('10:00');
        }

        $schedule->call(function () {
            $this->noOrderForTodayNotification();
        })->weekdays()->at('10:00');

        $schedule->call(function () {
            $this->noOrderForNextDayNotification();
        })->weekdays()->at('10:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }

    protected function noOrderForTodayNotification()
    {
        $users = User::query()
            ->where('settings->noOrderNotification', '1')
            ->whereDoesntHave('meals', function ($q) {
                return $q->whereDate('date', today());
            })
            ->get();

        Notification::send($users, new NoOrder(today()));
    }

    protected function noOrderForNextDayNotification()
    {
        $nextDay = today()->addWeekday();
        $users = User::query()
            ->where('settings->noOrderForNextDayNotification', '1')
            ->whereDoesntHave('meals', function ($q) use ($nextDay) {
                return $q->whereDate('date', $nextDay);
            })
            ->get();

        Notification::send($users, new NoOrder($nextDay));
    }
}
