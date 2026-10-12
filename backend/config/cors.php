<?php

/**
 * CORS Configuration
 * 
 * Controls which origins can make cross-origin requests to the API.
 * This is critical for security — it prevents unauthorized domains
 * from making API calls on behalf of users.
 * 
 * The FRONTEND_URL environment variable should be set to your
 * Vercel deployment URL in production.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Paths
    |--------------------------------------------------------------------------
    |
    | Only API routes need CORS headers. Static assets served by
    | the same origin don't need them.
    |
    */

    'paths' => ['api/*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Methods
    |--------------------------------------------------------------------------
    */

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | In production, this should only contain your Vercel frontend URL.
    | In development, localhost:5173 is allowed.
    |
    | Set FRONTEND_URL in your .env file to restrict this.
    |
    */

    'allowed_origins' => array_values(array_unique(array_filter([
        'http://localhost:5173',
        'http://localhost:3000',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:8000',
        'https://leanon-bot.xyz',
        'https://www.leanon-bot.xyz',
        ...array_map(
            fn($url) => rtrim(trim($url), '/'),
            explode(',', env('FRONTEND_URL', ''))
        ),
    ]))),

    'allowed_origins_patterns' => [
        '#^https://(.*\\.)?leanon-bot\\.xyz$#',
        '#^https://.*\\.vercel\\.app$#',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Headers
    |--------------------------------------------------------------------------
    |
    | Must include custom headers used by the encryption and HMAC
    | signing system, plus standard auth headers. Using ['*'] ensures
    | browser preflight requests are never rejected due to unexpected headers.
    |
    */

    'allowed_headers' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Exposed Headers
    |--------------------------------------------------------------------------
    |
    | Headers the browser is allowed to read from the response.
    | X-Encrypted tells the frontend whether to decrypt the response.
    |
    */

    'exposed_headers' => [
        'X-Encrypted',
        'Retry-After',
    ],

    /*
    |--------------------------------------------------------------------------
    | Max Age
    |--------------------------------------------------------------------------
    |
    | How long (in seconds) the browser should cache the CORS preflight
    | response. 86400 = 24 hours, reducing OPTIONS requests.
    |
    */

    'max_age' => 86400,

    /*
    |--------------------------------------------------------------------------
    | Supports Credentials
    |--------------------------------------------------------------------------
    |
    | Required for Sanctum cookie-based auth (if used).
    | Safe to keep true even with token-based auth.
    |
    */

    'supports_credentials' => true,
];
