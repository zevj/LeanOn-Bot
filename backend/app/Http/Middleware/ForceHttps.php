<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ForceHttps Middleware
 * 
 * Rejects non-HTTPS requests in production environments.
 * This prevents accidental exposure of tokens, encrypted payloads,
 * and session data over unencrypted connections.
 * 
 * Checks X-Forwarded-Proto because Render and Cloudflare terminate
 * SSL at the proxy layer, so the app server receives HTTP internally.
 */
class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip HTTPS enforcement in local development
        if (app()->environment('local', 'testing')) {
            return $next($request);
        }

        // Check if the request is HTTPS (directly or via proxy)
        // Render/Cloudflare set X-Forwarded-Proto to indicate the original scheme
        $isSecure = $request->secure() || $request->header('X-Forwarded-Proto') === 'https';

        if (!$isSecure) {
            return response()->json([
                'message' => 'HTTPS is required. Insecure requests are not allowed.'
            ], 403);
        }

        return $next($request);
    }
}
