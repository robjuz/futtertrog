<?php

namespace App\Providers;

use App\Deposit;
use App\Order;
use Cknow\Money\Money;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        setlocale(LC_MONETARY, 'de_DE.utf8');

        Paginator::useBootstrapThree(); //default.blade.php

        $this->app->bind('system_balance', function () {
            $depositesValue = Money::parse(Deposit::sum('value'));

            $payedOrdersValues = Money::sum(
                Money::parse(0),
                ...Order::whereNotNull('payed_at')->get()->pluck('subtotal')
            );

            return $depositesValue->subtract($payedOrdersValues);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
