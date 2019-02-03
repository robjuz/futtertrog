<?php

namespace App\Providers;

use App\Meal;
use App\OrderItem;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Meal' => 'App\Policies\MealPolicy',
        'App\Order' => 'App\Policies\OrderPolicy',
        'App\OrderItem' => 'App\Policies\OrderItemPolicy'
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function ($user, $ability) {
            if ($ability === 'order') {
                return null;
            }

            if ($ability === 'disorder') {
                return null;
            }

            if ($user->is_admin) {
                return true;
            }
        });

        Gate::define('order', function (User $user, Meal $meal) {
            return $meal->date_from > today() && today() < $meal->date_to;
        });

        Gate::define('disorder', function (User $user, Meal $meal) {
            return true;
//            return $user->meals->contains($meal);
        });
    }
}
