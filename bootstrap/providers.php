<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\MealProvidersServiceProvider;
use SocialiteProviders\Manager\ServiceProvider;

return [
    AppServiceProvider::class,
    MealProvidersServiceProvider::class,
    AuthServiceProvider::class,
    ServiceProvider::class,
];
