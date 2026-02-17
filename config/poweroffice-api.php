<?php

// config for Tor2r/PowerOfficeApi

return [

    /*
    |--------------------------------------------------------------------------
    | PowerOffice Environment
    |--------------------------------------------------------------------------
    |
    | Determines which PowerOffice API environment to use.
    | Supported: "production", "demo"
    |
    */

    'environment' => env('POWEROFFICE_ENVIRONMENT', 'demo'),

    /*
    |--------------------------------------------------------------------------
    | API Credentials
    |--------------------------------------------------------------------------
    */

    'app_key' => env('POWEROFFICE_APP_KEY'),
    'client_key' => env('POWEROFFICE_CLIENT_KEY'),
    'subscription_key' => env('POWEROFFICE_SUBSCRIPTION_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Environment URLs
    |--------------------------------------------------------------------------
    */

    'environments' => [
        'production' => [
            'base_url' => 'https://goapi.poweroffice.net/v2',
            'token_url' => 'https://goapi.poweroffice.net/OAuth/Token',
        ],
        'demo' => [
            'base_url' => 'https://goapi.poweroffice.net/demo/v2',
            'token_url' => 'https://goapi.poweroffice.net/Demo/OAuth/Token',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Cache TTL
    |--------------------------------------------------------------------------
    |
    | How long to cache the access token in seconds. The token expires after
    | 20 minutes, so we default to 15 minutes (900s) as a 5-minute buffer.
    |
    */

    'token_ttl' => env('POWEROFFICE_TOKEN_TTL', 900),

];
