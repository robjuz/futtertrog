<?php

namespace App\Providers;

use App\Console\Commands\CallAPizza;
use App\Console\Commands\Holzke;
use App\MealProviders\AbstractMealProvider;
use App\MealProviders\CallAPizzaService;
use App\MealProviders\HolzkeMealProvider;
use App\Services\MealService;
use Illuminate\Support\ServiceProvider;

class MealProvidersServiceProvider extends ServiceProvider
{
    private array $bundledProviders = [
        HolzkeMealProvider::class,
        CallAPizzaService::class
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

        $this->app->bind('mealProviders', function($app) {
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
