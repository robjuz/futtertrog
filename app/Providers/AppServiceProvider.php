<?php

namespace App\Providers;

use App\Models\Deposit;
use App\Models\Order;
use Cknow\Money\Money;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

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
                ...Order::with('orderItems.meal')->whereNotNull('payed_at')->get()->pluck('subtotal')
            );

            return $depositesValue->subtract($payedOrdersValues);
        });

        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('authentik', \SocialiteProviders\Authentik\Provider::class);
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
