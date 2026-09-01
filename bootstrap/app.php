<?php

use App\Http\Middleware\CastFormValuesToFloat;
use App\Http\Middleware\UserLocale;
use App\ScheduledJobs\NoOrderForNextDayNotification;
use App\ScheduledJobs\NoOrderForTodayNotification;
use App\ScheduledJobs\OpenOrdersForNextWeekNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'cast.float' => CastFormValuesToFloat::class,
        ]);

        $middleware->web(append: [
            UserLocale::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function (Schedule $schedule) {

        foreach (app('mealProviders') as $mealProvider => $name) {
            app($mealProvider)->configureSchedule($schedule);
        }

        $schedule->call(new NoOrderForTodayNotification)->weekdays()->at('10:00');

        $schedule->call(new NoOrderForNextDayNotification)->weekdays()->at('10:00');

        $schedule->call(new OpenOrdersForNextWeekNotification)
            ->days([4, 5])
            ->at('10:00');
    })
    ->create();
