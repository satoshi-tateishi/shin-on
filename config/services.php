<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
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

    'lineworks' => [
        'bot_id' => env('LINEWORKS_BOT_ID'),
        'bot_client_id' => env('LINEWORKS_DB_CLIENT_ID', env('LINEWORKS_CLIENT_ID')),
        'bot_client_secret' => env('LINEWORKS_DB_CLIENT_SECRET', env('LINEWORKS_CLIENT_SECRET')),
        'service_account' => env('LINEWORKS_SERVICE_ACCOUNT'),
        'private_key_path' => env('LINEWORKS_PRIVATE_KEY_PATH', 'lineworks/private_key.pem'),
        'api_base_url' => env('LINEWORKS_API_BASE_URL', 'https://www.worksapis.com/v1.0'),
        'auth_url' => env('LINEWORKS_AUTH_URL', 'https://auth.worksmobile.com/oauth2/v2.0/token'),
    ],

];
