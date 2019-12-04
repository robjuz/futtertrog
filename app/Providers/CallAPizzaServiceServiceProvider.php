<?php

namespace App\Providers;

use App\Meal;
use App\Services\CallAPizzaService;
use Illuminate\Support\ServiceProvider;

class CallAPizzaServiceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(
            CallAPizzaService::class,
            function () {
                return new CallAPizzaService();
            }
        );

        $this->app->singleton(
            Meal::PROVIDER_CALL_A_PIZZA.'_service',
            function () {
                return new CallAPizzaService();
            }
        );
    }
}
