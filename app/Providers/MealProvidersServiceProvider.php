<?php

namespace App\Providers;

use App\MealProviders\CallAPizzaMealProvider;
use App\MealProviders\FlaschenpostMealProvider;
use App\MealProviders\HolzkeMealProvider;
use Illuminate\Support\ServiceProvider;

class MealProvidersServiceProvider extends ServiceProvider
{
    private array $bundledProviders = [
        HolzkeMealProvider::class,
        CallAPizzaMealProvider::class,
        FlaschenpostMealProvider::class,
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
                return [$provider => $app->make($provider)->getName()];
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
