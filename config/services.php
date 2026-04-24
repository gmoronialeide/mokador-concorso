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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'azure_docintel' => [
        'endpoint' => env('AZURE_DOCINTEL_ENDPOINT'),
        'key' => env('AZURE_DOCINTEL_KEY'),
        'api_version' => env('AZURE_DOCINTEL_API_VERSION', '2024-11-30'),
    ],

    'concorso_alerts' => [
        'recipients' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('CONCORSO_ALERT_RECIPIENTS', '')),
        ))),
    ],

];
