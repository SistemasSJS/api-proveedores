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
    'notifications' => [
        'polling_interval' => env('NOTIFICATIONS_POLLING_INTERVAL', 30),
        'max_notifications' => env('NOTIFICATIONS_MAX_ITEMS', 50),
        'enable_sse' => env('NOTIFICATIONS_ENABLE_SSE', true),
    ],
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
    'recaptcha' => [
        'secret' => env('RECAPTCHA_SECRET_KEY'),
        'site' => env('RECAPTCHA_SITE_KEY'),
    ],
    'frontend' => [
        'url' => env('APP_FRONTEND_URL', 'http://localhost:8100'), // Valor por defecto
    ],

    'fcm' => [
        // Método moderno con Service Account
        'credentials' => env('FIREBASE_CREDENTIALS', 'firebase/service-account.json'),
        'project_id' => env('FCM_PROJECT_ID', 'app-proveedores-notificacion'),
        'sender_id' => env('FCM_SENDER_ID', '989092385974'),

        // Fallback para método legacy (si fuera necesario)
        'server_key' => env('FCM_SERVER_KEY', null),
    ],
];
