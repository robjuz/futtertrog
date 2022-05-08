<?php

namespace App\Providers;

use App\Events\NewOrderPossibilities;
use App\Events\NewOrderPossibility;
use App\Events\OrderUpdated;
use App\Listeners\SendNewOrderPossibilitiesNotification;
use App\Listeners\SendNewOrderPossibilityNotification;
use App\Listeners\SendOrderReopenedNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use SocialiteProviders\GitLab\GitLabExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        OrderUpdated::class => [
            SendOrderReopenedNotification::class,
        ],
        NewOrderPossibility::class => [
            SendNewOrderPossibilityNotification::class,
        ],
        NewOrderPossibilities::class => [
            SendNewOrderPossibilitiesNotification::class,
        ],
        SocialiteWasCalled::class => [
            GitLabExtendSocialite::class.'@handle',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
