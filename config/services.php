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

    'midtrans' => [
        'server_key' => env('PAYMENT_GATEWAY_SERVER_KEY'),
        'client_key' => env('PAYMENT_GATEWAY_CLIENT_KEY'),
        'is_production' => env('PAYMENT_GATEWAY_IS_PRODUCTION', false),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Maps Platform Services
    |--------------------------------------------------------------------------
    |
    | API Keys and default base location coordinates for Arsy Tenda.
    | Browser key is dedicated for Maps JavaScript API (restricted by HTTP Referrer).
    | Server key is used for backend Geocoding and Routes API requests.
    |
    */
    'google_maps' => [
        'client_key' => env('GOOGLE_MAPS_CLIENT_KEY', env('GOOGLE_MAPS_API_KEY')),
        'server_key' => env('GOOGLE_MAPS_SERVER_KEY', env('GOOGLE_MAPS_API_KEY')),
        'base_location' => [
            'name' => env('ARSY_TENDA_LOCATION_NAME', 'Kantor Arsy Tenda Pelaihari'),
            'address' => env('ARSY_TENDA_LOCATION_ADDRESS', 'Pelaihari, Kabupaten Tanah Laut, Kalimantan Selatan'),
            'latitude' => (float) env('ARSY_TENDA_LATITUDE', -3.7955),
            'longitude' => (float) env('ARSY_TENDA_LONGITUDE', 114.7712),
        ],
    ],

];
