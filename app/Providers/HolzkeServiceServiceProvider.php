<?php

namespace App\Providers;

use App\Services\HolzkeService;
use Illuminate\Support\ServiceProvider;

class HolzkeServiceServiceProvider extends ServiceProvider
{

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(
            HolzkeService::class,
            function () {
                return new HolzkeService();
            }
        );
    }
}
