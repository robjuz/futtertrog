<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        'App\Models\Meal' => 'App\Policies\MealPolicy',
        'App\Models\OrderItem' => 'App\Policies\OrderItemPolicy',
        'App\Models\Order' => 'App\Policies\OrderPolicy',
        'App\Models\Deposit' => 'App\Policies\DepositPolicy',
        'App\Models\User' => 'App\Policies\UserPolicy',
    ];

    public function boot(): void
    {
        Gate::define('access-administration', fn (User $user): bool => $user->is_admin && !$user->trashed());
        Gate::define('send-notifications', fn (User $user): bool => $user->can('access-administration'));
    }
}
