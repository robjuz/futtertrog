<?php

namespace App\Providers;

use App\MealProviders\Basic;
use App\MealProviders\CallAPizza;
use App\MealProviders\Flaschenpost;
use App\MealProviders\Holzke;
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
        foreach ($this->bundledProviders as $provider) {
            call_user_func([$provider, 'register'], $this->app);
        }

        $this->app->bind('mealProviders', function ($app) {
            return collect($this->bundledProviders)->mapWithKeys(function ($provider) use ($app) {
                return [class_basename($provider) => $app->make($provider)->getName()];
            })->all();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
