<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\Authenticate;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\TooManyHttpRequestsException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ── API Middleware Stack ──────────────────────────────────
        // Order matters! The stack runs in this order for each request:
        //
        // 1. ForceHttps      — reject non-HTTPS in production (first line of defense)
        // 2. PayloadSizeLimit — reject oversized requests before processing
        // 3. SecureHeaders    — attach security headers to every response
        // 4. SanitizeInput    — strip HTML/XSS from inputs
        // 5. VerifyHmac       — verify request signature + replay protection
        // 6. ApiEncryption    — decrypt request / encrypt response (existing)
        // 7. throttle:api-global — global rate limit (60/min per IP)
        $middleware->api(append: [
            \App\Http\Middleware\ForceHttps::class,
            \App\Http\Middleware\PayloadSizeLimit::class,
            \App\Http\Middleware\SecureHeaders::class,
            \App\Http\Middleware\SanitizeInput::class,
            \App\Http\Middleware\VerifyHmac::class,
            \App\Http\Middleware\ApiEncryption::class,
            'throttle:api-global',
        ]);

        // ── Middleware Aliases ────────────────────────────────────
        $middleware->alias([
            'auth' => Authenticate::class,
            'role' => RoleMiddleware::class,
        ]);

        // ── Trusted Proxies (Render + Cloudflare) ────────────────
        // Render terminates SSL at its proxy, so we must trust its
        // forwarded headers to correctly detect HTTPS and client IPs.
        //
        // When Cloudflare is enabled, we also trust Cloudflare's IP
        // ranges so that rate limiting uses the real client IP.
        $trustedProxies = ['*']; // Trust Render's proxy (required for X-Forwarded-Proto)

        $cfConfig = require __DIR__.'/../config/cloudflare.php';
        if ($cfConfig['enabled'] ?? false) {
            $cfIps = array_merge(
                $cfConfig['ipv4'] ?? [],
                $cfConfig['ipv6'] ?? []
            );
            $trustedProxies = array_merge($trustedProxies, $cfIps);
        }

        $middleware->trustProxies(
            at: $trustedProxies,
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
                     \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
                     \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
                     \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
                     \Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // ── Production-Safe Error Responses ──────────────────────
        // In production, we NEVER leak stack traces, file paths,
        // or internal error details. All errors return clean JSON.

        // 401 Unauthenticated — clear message without internals
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        });

        // 422 Validation errors — return field-level errors
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        // 429 Rate limited — JSON response with Retry-After
        $exceptions->render(function (TooManyHttpRequestsException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Too many requests. Please try again later.',
                ], 429)->withHeaders([
                    'Retry-After' => $e->getHeaders()['Retry-After'] ?? 60,
                ]);
            }
        });

        // 404 Model not found — clean JSON instead of HTML error page
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Resource not found.',
                ], 404);
            }
        });

        // Generic server errors — hide internals in production
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                // In production, return generic error; in dev, return details
                if (app()->environment('production')) {
                    // Log the full error server-side for debugging
                    \Illuminate\Support\Facades\Log::error('Unhandled exception', [
                        'message' => $e->getMessage(),
                        'file'    => $e->getFile(),
                        'line'    => $e->getLine(),
                        'trace'   => $e->getTraceAsString(),
                    ]);

                    return response()->json([
                        'message' => 'An internal error occurred. Please try again later.',
                    ], 500);
                }
            }
        });
    })->create();

