<?php

return [
    /*
     * Rollback: set USER_INTERFACE_BLADE=true and run php artisan config:cache.
     * Only dashboard, ordering and own histories switch. Profile, credentials,
     * policies, routes and stored data always retain the secured implementation.
     * Return to Vue with USER_INTERFACE_BLADE=false and refresh configuration.
     */
    'blade' => env('USER_INTERFACE_BLADE', false),
];
