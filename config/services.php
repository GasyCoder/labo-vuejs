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

    'orange_sms' => [
        'client_id' => env('ORANGE_SMS_CLIENT_ID'),
        'client_secret' => env('ORANGE_SMS_CLIENT_SECRET'),
        'sender_name' => env('ORANGE_SMS_SENDER_NAME', 'LA REFERENCE'),
        'sender_number' => env('ORANGE_SMS_SENDER_NUMBER', 'tel:+261341234567'),
        'auth_url' => env('ORANGE_SMS_AUTH_URL', 'https://api.orange.com/oauth/v3/token'),
        'api_url' => env('ORANGE_SMS_API_URL', 'https://api.orange.com/smsmessaging/v1/outbound'),
    ],

    'mapi_sms' => [
        'username' => env('MAPI_SMS_USERNAME'),
        'password' => env('MAPI_SMS_PASSWORD'),
        'api_url' => env('MAPI_SMS_URL', 'https://messaging.mapi.mg/api'),
    ],

];
