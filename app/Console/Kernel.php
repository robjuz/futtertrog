<?php

namespace App\Console;

use App\ScheduledJobs\NoOrderForNextDayNotification;
use App\ScheduledJobs\NoOrderForTodayNotification;
use App\ScheduledJobs\OpenOrdersForNextWeekNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Class Kernel.
 */
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

        $schedule->call(new NoOrderForTodayNotification)->weekdays()->at('10:00');

        $schedule->call(new NoOrderForNextDayNotification)->weekdays()->at('10:00');

        $schedule->call(new OpenOrdersForNextWeekNotification())
                 ->days([4, 5])
                 ->at('10:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
