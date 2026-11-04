<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    // Web billing. Create one Price per plan in the Stripe dashboard (monthly and
    // yearly as recurring prices, lifetime as a one-time price) and paste their
    // IDs here. The webhook endpoint is /stripe/webhook.
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'prices' => [
            'monthly' => env('STRIPE_PRICE_MONTHLY'),
            'yearly' => env('STRIPE_PRICE_YEARLY'),
            'lifetime' => env('STRIPE_PRICE_LIFETIME'),
        ],
    ],

    // Kingsleykhordpiano members get KhordQuiz included. See
    // App\Services\KingsleyKhordPiano\Client for the response shape expected.
    'kingsleykhordpiano' => [
        'base_url' => env('KKP_API_URL'),
        'token' => env('KKP_API_TOKEN'),
        // Lists subscribed members (paginated or not).
        'members_path' => env('KKP_MEMBERS_PATH', '/api/members'),
        // Looks up one member by email; {email} is replaced (URL-encoded).
        'member_path' => env('KKP_MEMBER_PATH', '/api/members/lookup?email={email}'),
        // KhordQuiz plan granted when the member's record doesn't map to one.
        'default_plan' => env('KKP_DEFAULT_PLAN', 'yearly'),
        'timeout' => (int) env('KKP_API_TIMEOUT', 8),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
