<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => env('SES_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
    ],

    'nexmo' => [
        'key' => env('NEXMO_KEY'),
        'secret' => env('NEXMO_SECRET'),
        'sms_from' => '15556666666',
    ],

    'gitlab' => [
        'enabled' => env('LOGIN_WITH_GITLAB', false),
        'client_id' => env('GITLAB_CLIENT_ID'),
        'client_secret' => env('GITLAB_CLIENT_SECRET'),
        'instance_uri' => env('GITLAB_URL'),
        'redirect' => '/login/gitlab/callback',
    ],

    'holzke' => [
        'login' => env('HOLZKE_LOGIN'),
        'password' => env('HOLZKE_PASSWORD'),
        'schedule' => env('HOLZKE_SCHEDULE'),
    ],

    'call_a_pizza' => [
        'location' => env('CALL_A_PIZZA_LOCATION', 'dresden_loebtau_sued'),
        'meals' => env('CALL_A_PIZZA_MEALS', ['pizza/pizza-klassiker', 'burger']),
    ],
];
