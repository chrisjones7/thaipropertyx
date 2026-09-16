<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, Google Maps, and more. This file provides the
    | de facto location for this type of information, allowing packages to
    | have a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Maps
    |--------------------------------------------------------------------------
    |
    | Used by TPX for property location search, map display, geocoding and
    | automatic latitude / longitude selection.
    |
    | The API key itself is stored securely in the .env file and should never
    | be hard-coded into the application source.
    |
    */

    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
    ],

];
