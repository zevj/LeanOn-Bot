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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'turnstile' => [
        'secret' => env('TURNSTILE_SECRET_KEY'),
    ],

    // ── AI Analytics & Insights ───────────────────────────────
    'ai_insights' => [
        'provider'          => env('AI_INSIGHTS_PROVIDER', 'gemini'),
        'gemini_key'        => env('GEMINI_API_KEY'),
        'gemini_model'      => env('AI_INSIGHTS_GEMINI_MODEL', 'gemini-2.5-flash'),
        'openai_key'        => env('OPENAI_API_KEY'),
        'cache_ttl'         => env('AI_INSIGHTS_CACHE_TTL', 86400), // 24 hours
        'cooldown_ttl'      => env('AI_INSIGHTS_COOLDOWN_TTL', 21600), // 6 hours
        'max_retries'       => env('AI_INSIGHTS_MAX_RETRIES', 2),
        'timeout'           => env('AI_INSIGHTS_TIMEOUT', 25),
        'max_output_tokens' => env('AI_INSIGHTS_MAX_OUTPUT_TOKENS', 1200),
    ],

];
