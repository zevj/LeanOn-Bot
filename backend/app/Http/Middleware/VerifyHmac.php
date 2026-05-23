<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * VerifyHmac Middleware
 * 
 * Validates HMAC-SHA256 request signatures to ensure:
 * 1. Request authenticity — it came from our frontend, not a third party
 * 2. Request integrity — the payload wasn't tampered with in transit
 * 3. Replay protection — the same request can't be sent twice
 * 
 * Flow:
 * Frontend: payload + timestamp + nonce → AES encrypt → HMAC sign
 * Backend:  verify timestamp → verify nonce unused → verify HMAC → AES decrypt
 * 
 * This middleware runs BEFORE the ApiEncryption middleware in the stack.
 * 
 * Headers required: X-Timestamp, X-Nonce, X-Signature
 * Skipped for: GET/HEAD/OPTIONS requests, and file uploads (multipart/form-data)
 */
class VerifyHmac
{
    /**
     * Maximum age of a request in seconds.
     * Requests older than this are rejected to prevent replay attacks
     * with captured requests.
     */
    private const REQUEST_EXPIRY_SECONDS = 60;

    /**
     * How long to remember used nonces in cache (seconds).
     * Must be longer than REQUEST_EXPIRY_SECONDS to prevent edge cases.
     */
    private const NONCE_TTL_SECONDS = 120;

    public function handle(Request $request, Closure $next): Response
    {
        // Skip HMAC verification for safe HTTP methods and file uploads
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $timestamp = $request->header('X-Timestamp');
        $nonce     = $request->header('X-Nonce');
        $signature = $request->header('X-Signature');

        // All three headers are required for signed requests
        if (!$timestamp || !$nonce || !$signature) {
            // If X-Encrypted is not set (non-encrypted request), skip HMAC too
            // This allows non-encrypted endpoints to work without HMAC
            if ($request->header('X-Encrypted') !== 'true') {
                return $next($request);
            }

            Log::channel('security')->warning('HMAC headers missing', [
                'ip'   => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'message' => 'Request signature is required.'
            ], 403);
        }

        // Step 1: Validate timestamp freshness
        $now = time();
        $requestTime = (int) $timestamp;
        
        if (abs($now - $requestTime) > self::REQUEST_EXPIRY_SECONDS) {
            Log::channel('security')->warning('Expired request timestamp', [
                'ip'         => $request->ip(),
                'path'       => $request->path(),
                'timestamp'  => $timestamp,
                'server_time' => $now,
                'drift'      => abs($now - $requestTime),
            ]);

            return response()->json([
                'message' => 'Request has expired.'
            ], 403);
        }

        // Step 2: Check nonce hasn't been used (replay protection)
        $nonceKey = 'hmac_nonce:' . $nonce;
        
        if (Cache::has($nonceKey)) {
            Log::channel('security')->warning('Replay attack detected — nonce reused', [
                'ip'    => $request->ip(),
                'path'  => $request->path(),
                'nonce' => $nonce,
            ]);

            return response()->json([
                'message' => 'Request rejected — possible replay attack.'
            ], 403);
        }

        // Store nonce in cache to prevent reuse
        Cache::put($nonceKey, true, self::NONCE_TTL_SECONDS);

        // Step 3: Verify HMAC signature
        $hmacKeyHex = config('app.hmac_secret_key');
        
        if (empty($hmacKeyHex)) {
            Log::channel('security')->error('HMAC_SECRET_KEY is not configured');
            return response()->json([
                'message' => 'Server security configuration error.'
            ], 500);
        }

        // Convert hex secret key to raw binary bytes to match frontend Web Crypto API import
        $hmacKey = hex2bin($hmacKeyHex);

        // Build the signing string: body content + timestamp + nonce
        $body = $request->getContent();
        $signingString = "{$body}.{$timestamp}.{$nonce}";
        
        // Compute expected signature
        $expectedSignature = hash_hmac('sha256', $signingString, $hmacKey);

        // Constant-time comparison to prevent timing attacks
        if (!hash_equals($expectedSignature, $signature)) {
            Log::channel('security')->warning('Invalid HMAC signature', [
                'ip'   => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'message' => 'Invalid request signature.'
            ], 403);
        }

        return $next($request);
    }

    /**
     * Determine if HMAC verification should be skipped for this request.
     */
    private function shouldSkip(Request $request): bool
    {
        // GET, HEAD, OPTIONS don't carry payloads that need signing
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return true;
        }

        // File uploads use multipart/form-data — can't HMAC sign these cleanly
        if (str_contains($request->header('Content-Type', ''), 'multipart/form-data')) {
            return true;
        }

        // Admin action endpoints with no sensitive body — already protected by
        // auth:sanctum + role:guidance, HMAC signing adds no value here and
        // breaks when Cloudflare/Render proxies modify the request in transit.
        $hmacExemptPaths = [
            'api/admin/analytics/insights/generate',
            'api/admin/crisis-alerts',
        ];

        foreach ($hmacExemptPaths as $path) {
            if (str_contains($request->path(), $path)) {
                return true;
            }
        }

        return false;
    }
}
