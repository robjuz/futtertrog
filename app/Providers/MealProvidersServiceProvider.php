<?php

namespace App\Providers;

use App\MealProviders\CallAPizza;
use App\MealProviders\Flaschenpost;
use App\MealProviders\Gourmetta;
use App\MealProviders\Holzke;
use App\MealProviders\Internal;
use Illuminate\Support\ServiceProvider;

class MealProvidersServiceProvider extends ServiceProvider
{
    private array $bundledProviders = [
        Holzke::class,
        CallAPizza::class,
        Flaschenpost::class,
        Gourmetta::class,
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
        Internal::register($this->app);

        $registeredProviders = [];

        foreach ($this->bundledProviders as $provider) {
            if (call_user_func([$provider, 'register'], $this->app)) {
                $registeredProviders[class_basename($provider)] = $this->app->make($provider)->getName();
            }
        }

        $this->app->bind('mealProviders', fn () => $registeredProviders);
    }
}
