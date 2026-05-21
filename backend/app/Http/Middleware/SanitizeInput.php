<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SanitizeInput Middleware
 * 
 * Strips HTML/script tags from all string inputs to prevent XSS
 * and HTML injection attacks. Applied globally to all API requests.
 * 
 * Skipped for: password fields (which may contain special chars)
 * and fields that are already encrypted payloads.
 */
class SanitizeInput
{
    /**
     * Fields that should NOT be sanitized.
     * Passwords may contain angle brackets; encrypted payloads are base64.
     */
    private array $except = [
        'password',
        'current_password',
        'new_password',
        'password_confirmation',
        'new_password_confirmation',
        'payload', // AES-encrypted payload — already base64, don't strip
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();
        $sanitized = $this->sanitize($input);
        $request->merge($sanitized);

        return $next($request);
    }

    /**
     * Recursively sanitize all string values in the input array.
     */
    private function sanitize(array $data, string $parentKey = ''): array
    {
        foreach ($data as $key => $value) {
            // Skip exempt fields
            if (in_array($key, $this->except, true)) {
                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->sanitize($value, $key);
            } elseif (is_string($value)) {
                // Strip HTML tags and trim whitespace
                $data[$key] = trim(strip_tags($value));
            }
        }

        return $data;
    }
}
