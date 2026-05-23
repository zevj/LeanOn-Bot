<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SessionLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use App\Services\ApiEncryptionService;

class GoogleAuthController extends Controller
{
    protected ApiEncryptionService $encryptionService;

    public function __construct(ApiEncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
    }

    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectToGoogle()
    {
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver('google');

        if (config('app.env') === 'local') {
            $driver->setHttpClient(new Client(['verify' => false]));
        }

        return $driver
            ->stateless()
            ->with([
                'prompt' => 'select_account',
            ])
            ->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleGoogleCallback()
    {
        try {
            /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
            $driver = Socialite::driver('google');

            if (config('app.env') === 'local') {
                $driver->setHttpClient(new Client(['verify' => false]));
            }

            $googleUser = $driver->stateless()->user();
        } catch (\Exception $e) {
            Log::error('Google Auth Error: ' . $e->getMessage());
            return redirect(env('FRONTEND_URL', 'http://localhost:5173') . '/login?error=google_auth_failed');
        }

        $email = $googleUser->getEmail();

        // 1. Strict Domain Restriction
        if (!str_ends_with($email, '@gordoncollege.edu.ph')) {
            return redirect(env('FRONTEND_URL', 'http://localhost:5173') . '/login?error=invalid_domain');
        }

        // 2. Check if user exists
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Per user request: do not auto-create
            return redirect(env('FRONTEND_URL', 'http://localhost:5173') . '/login?error=user_not_found');
        }

        if (is_null($user->email_verified_at)) {
            $user->update(['email_verified_at' => now()]);
        }

        // Close any existing open sessions for this user before starting a new one
        SessionLog::where('user_id', $user->id)
            ->whereNull('session_end')
            ->update(['session_end' => Carbon::now()]);

        // 4. Log the user in and generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Create session log entry
        $sessionLog = SessionLog::create([
            'user_id'       => $user->id,
            'session_start' => Carbon::now(),
        ]);

        // 5. Redirect back to frontend with encrypted token and user info
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $userData = json_encode([
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'role' => $user->role,
            'terms_accepted_at' => $user->terms_accepted_at,
        ]);

        try {
            $encryptedPayload = $this->encryptionService->encrypt([
                'token' => $token,
                'user' => $userData,
                'session_log_id' => (string) $sessionLog->id
            ]);

            return redirect("{$frontendUrl}/auth/google/callback?payload=" . urlencode($encryptedPayload));
        } catch (\Exception $e) {
            Log::error('Google Auth Redirect Encryption Failed: ' . $e->getMessage());
            return redirect("{$frontendUrl}/login?error=google_auth_failed");
        }
    }
}
