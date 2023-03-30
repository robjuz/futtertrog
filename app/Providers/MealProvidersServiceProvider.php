<?php

namespace App\Providers;

use App\MealProviders\Basic;
use App\MealProviders\CallAPizza;
use App\MealProviders\Flaschenpost;
use App\MealProviders\Holzke;
use App\MealProviders\Weekly;
use Illuminate\Support\ServiceProvider;

class MealProvidersServiceProvider extends ServiceProvider
{
    private array $bundledProviders = [
        Basic::class,
        Holzke::class,
        CallAPizza::class,
        Flaschenpost::class,
    ];

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $registeredProviders = [];

        foreach ($this->bundledProviders as $provider) {
            if (call_user_func([$provider, 'register'], $this->app)) {
                $registeredProviders[class_basename($provider)] = $this->app->make($provider)->getName();
            }
        }

        $this->app->bind('mealProviders', fn () => $registeredProviders);
    }
}
