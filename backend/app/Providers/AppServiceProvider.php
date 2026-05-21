<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ── HTTPS URL Generation ─────────────────────────────────
        // Force all generated URLs to use HTTPS in production.
        // This ensures asset URLs, redirects, and route URLs are
        // always secure when behind Render/Cloudflare proxies.
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // ── Model Strict Mode ────────────────────────────────────
        // In non-production, enable strict mode to catch:
        // - N+1 queries (lazy loading)
        // - Assigning non-fillable attributes
        // - Accessing undefined attributes
        // This helps catch performance issues during development.
        Model::shouldBeStrict(!app()->isProduction());

        // ── Rate Limiters ────────────────────────────────────────
        // Each limiter is referenced by name in route middleware.
        // Returns JSON with Retry-After header on limit exceeded.

        // Login: 5 attempts per minute per IP
        // Prevents brute-force password attacks
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many login attempts. Please try again later.',
                    ], 429, $headers);
                });
        });

        // OTP: 3 attempts per minute per IP
        // Prevents OTP flooding and brute-force OTP guessing
        RateLimiter::for('otp', function (Request $request) {
            return Limit::perMinute(3)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many OTP requests. Please wait before trying again.',
                    ], 429, $headers);
                });
        });

        // Chat: 20 messages per minute per user (or IP for guests)
        // Prevents AI API abuse that could exhaust free-tier quotas
        RateLimiter::for('chat', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();
            return Limit::perMinute(20)
                ->by($key)
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'You are sending messages too quickly. Please slow down.',
                    ], 429, $headers);
                });
        });

        // Password Reset: 3 attempts per minute per IP
        // Prevents password reset abuse and email flooding
        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(3)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many password reset requests. Please wait.',
                    ], 429, $headers);
                });
        });

        // File Upload: 5 uploads per minute per authenticated user
        // Prevents storage abuse on free-tier hosting
        RateLimiter::for('upload', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many upload attempts. Please wait.',
                    ], 429, $headers);
                });
        });

        // Global API: 60 requests per minute per IP
        // Catch-all protection against automated scanning and abuse
        RateLimiter::for('api-global', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Rate limit exceeded. Please try again later.',
                    ], 429, $headers);
                });
        });
    }
}
