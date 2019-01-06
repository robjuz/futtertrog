<?php

namespace App\Console;

use App\Notifications\NoOrderForToday;
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
            $schedule->command('import:holzke')->fridays()->at('10:00');
        }

        $schedule->call(function () {
            $this->noOrderForTodayNotification();
        })->dailyAt('10:00');
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

        Notification::send($users, new NoOrderForToday());
    }
}
