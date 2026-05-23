<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SecureHeaders Middleware
 * 
 * Attaches production-grade security headers to every HTTP response.
 * These headers instruct browsers to enforce security policies that
 * prevent clickjacking, MIME-sniffing, XSS, and data leakage.
 */
class SecureHeaders
{
    /**
     * Security headers applied to every response.
     * 
     * Each header addresses a specific attack vector:
     * - X-Frame-Options: Prevents page from being embedded in iframes (clickjacking)
     * - X-Content-Type-Options: Prevents MIME-type sniffing attacks
     * - X-XSS-Protection: Legacy XSS filter for older browsers
     * - Referrer-Policy: Controls how much referrer info is sent with requests
     * - Permissions-Policy: Disables browser APIs the app doesn't need
     */
    private array $securityHeaders = [
        'X-Frame-Options'       => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection'      => '1; mode=block',
        'Referrer-Policy'        => 'strict-origin-when-cross-origin',
        'Permissions-Policy'     => 'camera=(), microphone=(), geolocation=(), payment=()',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Apply all static security headers
        foreach ($this->securityHeaders as $header => $value) {
            $response->headers->set($header, $value);
        }

        // Content-Security-Policy — restrict resource loading to trusted sources
        // Allows: self, inline styles (Vue needs this), Google Fonts, and the API domain
        $frontendUrl = config('app.frontend_url', '*');
        $csp = implode('; ', [
            "default-src 'self'",
            // 'unsafe-eval' is required by the marked.js markdown library used to render bot responses
            "script-src 'self' 'unsafe-eval' https://challenges.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com https://cdnjs.cloudflare.com",
            "font-src 'self' https://fonts.gstatic.com https://unpkg.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: blob: https://api.leanon-bot.xyz https://leanon-backend-rdy7.onrender.com https://placehold.co https://placeholder.co http://127.0.0.1:8000 http://localhost:8000",
            "connect-src 'self' {$frontendUrl} https://challenges.cloudflare.com",
            "frame-src 'self' https://challenges.cloudflare.com",
            // Cloudflare Turnstile uses Web Workers internally
            "worker-src 'self' blob: https://challenges.cloudflare.com",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        // HSTS — force HTTPS for 1 year (only in production, browsers cache this)
        if (app()->environment('production')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        // Remove headers that leak server information
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
