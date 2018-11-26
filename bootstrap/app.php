<?php

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
            'cast.float' => \App\Http\Middleware\CastFormValuesToFloat::class,
            'enableLoginWithGitlab' => \App\Http\Middleware\EnableLoginWithGitlab::class,
        ]);

        $middleware->append(\App\Http\Middleware\UserLocale::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();