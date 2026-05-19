<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\ApiEncryptionService;
use Illuminate\Support\Facades\Log;

class ApiEncryption
{
    protected ApiEncryptionService $encryptionService;

    public function __construct(ApiEncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Decrypt incoming request if it contains encrypted payload and header is present
        if ($request->header('X-Encrypted') === 'true' && $request->has('payload')) {
            try {
                $payload = $request->input('payload');
                $decrypted = $this->encryptionService->decrypt($payload);

                if (is_array($decrypted)) {
                    // Replace request input with decrypted data
                    $request->replace($decrypted);
                }
            } catch (\Exception $e) {
                Log::error('API Request Decryption Failed: ' . $e->getMessage());
                return response()->json([
                    'message' => 'Decryption failure. Secure payload is corrupted or invalid.'
                ], 400);
            }
        }

        // 2. Process the request through the stack
        $response = $next($request);

        // 3. Encrypt the outgoing JSON response if E2E encryption is active
        if ($request->header('X-Encrypted') === 'true') {
            // Only encrypt typical JSON responses (JsonResponse or responses with content type application/json)
            $isJson = $response instanceof \Illuminate\Http\JsonResponse || 
                      str_contains($response->headers->get('Content-Type', ''), 'application/json');

            // Skip binary/file responses, redirects, and non-JSON responses
            if ($isJson && !$response->headers->get('Content-Disposition')) {
                $content = $response->getContent();
                $data = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    try {
                        $encryptedPayload = $this->encryptionService->encrypt($data);
                        
                        $response->setContent(json_encode([
                            'payload' => $encryptedPayload
                        ]));
                        
                        $response->headers->set('X-Encrypted', 'true');
                    } catch (\Exception $e) {
                        Log::error('API Response Encryption Failed: ' . $e->getMessage());
                        return response()->json([
                            'message' => 'Encryption failure. Secure channel could not encrypt the response.'
                        ], 500);
                    }
                }
            }
        }

        return $response;
    }
}
