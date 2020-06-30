<?php

namespace App\Providers;

use App\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Meal' => 'App\Policies\MealPolicy',
        'App\OrderItem' => 'App\Policies\OrderItemPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function ($user, $ability, $arguments) {
            if ($user->is_admin and ! ($arguments[0] instanceof User) and $ability !== 'delete') {
                return true;
            }
        });
    }
}
