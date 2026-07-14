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

    // IndexNow (Bing / Yandex / Seznam) — powiadamianie wyszukiwarek o zmianach URL.
    // Klucz weryfikacyjny hostowany dynamicznie pod /{key}.txt (trasa indexnow.key).
    // Pusty klucz = integracja wyłączona (no-op), więc lokalnie nic nie pinguje.
    'indexnow' => [
        'key' => env('INDEXNOW_KEY'),
        // Endpoint huba IndexNow (przekazuje ping do Bing i pozostałych silników).
        'endpoint' => env('INDEXNOW_ENDPOINT', 'https://api.indexnow.org/indexnow'),
    ],

];
