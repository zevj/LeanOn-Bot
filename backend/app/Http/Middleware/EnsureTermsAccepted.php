<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTermsAccepted
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Guidance counselors and admin staff do not block on student terms modal
        if ($user && in_array($user->role, ['guidance', 'admin'], true)) {
            return $next($request);
        }

        if ($user && is_null($user->terms_accepted_at)) {
            return response()->json([
                'status' => 'TERMS_REQUIRED',
                'message' => 'You must accept the terms and conditions to proceed.'
            ], 403);
        }

        return $next($request);
    }
}
