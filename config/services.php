<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'gitlab' => [
        'host' => env('GITLAB_URL'),
        'client_id' => env('GITLAB_CLIENT_ID'),
        'client_secret' => env('GITLAB_CLIENT_SECRET'),
        'redirect' => '/login/oauth/gitlab/callback',
    ],
    'authentik' => [
        'base_url' => env('AUTHENTIK_BASE_URL'),
        'client_id' => env('AUTHENTIK_CLIENT_ID'),
        'client_secret' => env('AUTHENTIK_CLIENT_SECRET'),
        'redirect' => '/login/oauth/authentik/callback'
    ],


    'holzke' => [
        'enabled' =>  (bool) env('HOLZKE_ENABLED', false),
        'login' => env('HOLZKE_LOGIN'),
        'password' => env('HOLZKE_PASSWORD'),
        'schedule' =>  (bool) env('HOLZKE_SCHEDULE', false),
    ],

    'gourmetta' => [
        'enabled' =>  (bool) env('GOURMETTA_ENABLED', false),
        'login' => env('GOURMETTA_LOGIN'),
        'password' => env('GOURMETTA_PASSWORD'),
        'schedule' =>  (bool) env('GOURMETTA_SCHEDULE'),
    ],

    'call_a_pizza' => [
        'enabled' =>  (bool) env('CALL_A_PIZZA_ENABLED', false),
        'location' => env('CALL_A_PIZZA_LOCATION', 'dresden_loebtau_sued'),
        'meals' => json_decode(env('CALL_A_PIZZA_CATEGORIES', "[\"pizza/pizza-klassiker\", \"burger\"]")),
    ],

    'flaschenpost' => [
        'enabled' =>  (bool) env('FLASCHENPOST_ENABLED', false),
        'zipcode' => env('FLASCHENPOST_ZIPCODE', '01159'),
        'categories' => json_decode(env('FLASCHENPOST_CATEGORIES', "[\"limonade/cola\"]")),
    ],
];
