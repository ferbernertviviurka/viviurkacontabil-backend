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

    'nf' => [
        'provider' => env('NF_PROVIDER', 'focus'),
        'api_key' => env('NF_API_KEY'),
        'api_url' => env('NF_API_URL'),
    ],

    'payment' => [
        'provider' => env('PAYMENT_PROVIDER', 'mercadopago'),
    ],

    'mercadopago' => [
        'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
        'public_key' => env('MERCADOPAGO_PUBLIC_KEY'),
        'production' => env('MERCADOPAGO_PRODUCTION', false),
    ],

    'ai' => [
        'provider' => env('AI_PROVIDER', 'clarifai'),
    ],

    'clarifai' => [
        'api_key' => env('CLARIFAI_API_KEY'),
        'user_id' => env('CLARIFAI_USER_ID', 'openai'),
        'app_id' => env('CLARIFAI_APP_ID', 'chat-completion'),
        'model' => env('CLARIFAI_MODEL', 'gpt-oss-120b'),
        'model_version_id' => env('CLARIFAI_MODEL_VERSION_ID', '1c1365f924224107a9cd72b0a9e633a6'),
    ],

];
