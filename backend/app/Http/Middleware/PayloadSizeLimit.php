<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * PayloadSizeLimit Middleware
 * 
 * Rejects request bodies that exceed a configurable size limit.
 * This prevents abuse via oversized payloads that consume memory
 * and processing time on free-tier servers.
 * 
 * Default: 64KB for API requests, 5MB for file uploads.
 * File uploads (multipart/form-data) use a higher limit.
 */
class PayloadSizeLimit
{
    /**
     * Maximum payload size in bytes.
     * API requests: 64KB (65,536 bytes) — generous for JSON payloads.
     * File uploads: 5MB (5,242,880 bytes) — matches profile image limit.
     */
    private const API_MAX_SIZE = 65536;     // 64 KB
    private const UPLOAD_MAX_SIZE = 5242880; // 5 MB

    public function handle(Request $request, Closure $next): Response
    {
        $contentLength = $request->header('Content-Length', 0);
        $isMultipart = str_contains(
            $request->header('Content-Type', ''),
            'multipart/form-data'
        );

        $maxSize = $isMultipart ? self::UPLOAD_MAX_SIZE : self::API_MAX_SIZE;

        if ($contentLength > $maxSize) {
            Log::channel('security')->warning('Oversized payload rejected', [
                'ip'             => $request->ip(),
                'path'           => $request->path(),
                'content_length' => $contentLength,
                'max_allowed'    => $maxSize,
                'is_upload'      => $isMultipart,
            ]);

            return response()->json([
                'message' => 'Request payload is too large.',
            ], 413);
        }

        return $next($request);
    }
}
