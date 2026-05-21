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

    'allowed_origins' => array_filter([
        env('FRONTEND_URL', 'http://localhost:5173'),
        // Add additional origins if needed (e.g., staging)
    ]),

    'allowed_origins_patterns' => [],

    /*
    |--------------------------------------------------------------------------
    | Allowed Headers
    |--------------------------------------------------------------------------
    |
    | Must include custom headers used by the encryption and HMAC
    | signing system, plus standard auth headers.
    |
    */

    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'Accept',
        'Origin',
        'X-Requested-With',
        // E2E Encryption header
        'X-Encrypted',
        // HMAC signing headers
        'X-Timestamp',
        'X-Nonce',
        'X-Signature',
    ],

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
