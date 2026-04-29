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

    'mailgun' => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme'   => 'https',
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

    'fcm' => [
        'project_id' => env('FCM_PROJECT_ID'),
        // Either inline JSON (recommended for hosting platforms) or a path to the service-account JSON file.
        'credentials_json' => env('FCM_CREDENTIALS_JSON'),
        'credentials_path' => env('FCM_CREDENTIALS_PATH'),
        // Public Web Push config used by the browser SDK. Exposed to JS via a meta tag.
        'web' => [
            'api_key' => env('FCM_WEB_API_KEY'),
            'auth_domain' => env('FCM_WEB_AUTH_DOMAIN'),
            'project_id' => env('FCM_PROJECT_ID'),
            'messaging_sender_id' => env('FCM_WEB_MESSAGING_SENDER_ID'),
            'app_id' => env('FCM_WEB_APP_ID'),
            'vapid_key' => env('FCM_WEB_VAPID_KEY'),
        ],
    ],

];
