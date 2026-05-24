<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
// use Google\Client as GoogleClient;
use App\Models\User;
use App\Models\PasswordOtp;
use App\Models\EmailOtp;
use App\Models\SessionLog;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use App\Services\MailService;

class AuthController extends Controller
{
    protected MailService $mailService;

    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }

    public function login(Request $request)
    {
        // ✅ Validate input
        $request->validate([
            'email' => [
                'required',
                'regex:/^[a-zA-Z0-9._%+\-]+@gordoncollege\.edu\.ph$/'
            ],
            'password' => 'required|min:6',
            'turnstile_token' => 'required|string'
        ], [
            'email.regex' => 'Only Gordon College email addresses are allowed.',
            'turnstile_token.required' => 'Security check is required.'
        ]);

        // ✅ Verify Turnstile Token with Cloudflare
        $secretKey = config('services.turnstile.secret');
        if (empty($secretKey)) {
            Log::error('Turnstile secret key is not configured in services.php.');
            return response()->json([
                'message' => 'Internal server error. CAPTCHA configuration missing.'
            ], 500);
        }

        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => $secretKey,
            'response' => $request->turnstile_token,
            'remoteip' => $request->ip(),
        ]);

        if (!$response->successful() || !$response->json('success')) {
            Log::warning('Turnstile verification failed', [
                'ip' => $request->ip(),
                'response' => $response->json(),
            ]);
            return response()->json([
                'message' => 'Security check failed. Please try again.'
            ], 422);
        }
        
        // ✅ Attempt login
        if (!Auth::attempt([
            'email' => $request->email,
            'password' => $request->password
        ])) {
            return response()->json([
                'message' => 'Invalid email or password'
            ], 401);
        }

        // ✅ Get authenticated user
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 🔐 First-time login or verification expired (older than 30 days): require email OTP verification
        if (is_null($user->email_verified_at) || $user->email_verified_at->lt(now()->subDays(30))) {
            if (!is_null($user->email_verified_at)) {
                $user->update(['email_verified_at' => null]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            $expiresAt = $this->sendLoginOtp($user);

            return response()->json([
                'status' => 'OTP_REQUIRED',
                'message' => 'Please verify your email (verification is required every 30 days)',
                'token' => $token,
                'user' => $user,
                'expires_at' => $expiresAt
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Close any existing open sessions for this user before starting a new one
        SessionLog::where('user_id', $user->id)
            ->whereNull('session_end')
            ->update(['session_end' => Carbon::now()]);

        // Create new session log entry
        $sessionLog = SessionLog::create([
            'user_id'       => $user->id,
            'session_start' => Carbon::now(),
        ]);

        return response()->json([
            'message'        => 'Login successful',
            'token'          => $token,
            'user'           => $user,
            'session_log_id' => $sessionLog->id,
        ]);
    }



public function verifyOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'otp' => 'required'
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    // Find latest active OTP from email_otps table
    $otpRecord = EmailOtp::where('user_id', $user->id)
        ->whereNull('used_at')
        ->latest()
        ->first();

    if (!$otpRecord) {
        return response()->json(['message' => 'No active OTP found. Please request a new one.'], 400);
    }

    // Check brute-force attempts
    if ($otpRecord->hasExceededAttempts()) {
        return response()->json(['message' => 'Too many failed attempts. Please request a new OTP.'], 429);
    }

    // Increment attempts
    $otpRecord->increment('attempts');

    // Check expiration
    if ($otpRecord->isExpired()) {
        return response()->json(['message' => 'OTP expired'], 400);
    }

    // Check OTP match (hashed)
    if (!Hash::check($request->otp, $otpRecord->otp_code)) {
        return response()->json(['message' => 'Invalid OTP'], 400);
    }

    // ✅ Mark OTP as used
    $otpRecord->update(['used_at' => now()]);

    // ✅ Mark email as verified
    $user->update(['email_verified_at' => now()]);
    $user->refresh();

    // Close any existing open sessions for this user before starting a new one (e.g. from the login attempt)
    SessionLog::where('user_id', $user->id)
        ->whereNull('session_end')
        ->update(['session_end' => Carbon::now()]);

    // Issue fresh token
    $token = $user->createToken('auth_token')->plainTextToken;

    // Create a new session log for the verified session
    $sessionLog = SessionLog::create([
        'user_id'       => $user->id,
        'session_start' => Carbon::now(),
    ]);

    return response()->json([
        'message' => 'Email verified successfully',
        'token' => $token,
        'user' => $user,
        'session_log_id' => $sessionLog->id,
    ]);
}

public function resendOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email'
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    // Prevent resend if already verified
    if (!is_null($user->email_verified_at)) {
        return response()->json(['message' => 'Account already verified'], 400);
    }

    // Rate limit: check if OTP was sent less than 60 seconds ago
    $lastOtp = EmailOtp::where('user_id', $user->id)->latest()->first();
    if ($lastOtp && $lastOtp->created_at->diffInSeconds(now()) < 60) {
        return response()->json([
            'message' => 'Please wait before requesting a new OTP'
        ], 429);
    }

    $expiresAt = $this->sendLoginOtp($user);

    return response()->json([
        'message' => 'OTP resent successfully',
        'expires_at' => $expiresAt
    ]);
}

public function sendOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email'
    ]);

    $otp = rand(100000, 999999);
    $expiresAt = now()->addMinutes(5);

    PasswordOtp::updateOrCreate(
        ['email' => $request->email],
        [
            'otp' => Hash::make($otp),
            'expires_at' => $expiresAt
        ]
    );

    // ✅ SEND EMAIL VIA API
    $this->mailService->sendOtp($request->email, $otp, 'forgot');

    return response()->json([
        'message' => 'OTP sent',
        'expires_at' => $expiresAt
    ]);
}

public function verifyForgotPasswordOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'otp' => 'required'
    ]);

    $record = PasswordOtp::where('email', $request->email)->first();

    if (!$record) {
        return response()->json(['message' => 'Invalid OTP'], 400);
    }

    // ✅ FIX: HASH CHECK
    if (!Hash::check($request->otp, $record->otp)) {
        return response()->json(['message' => 'Invalid OTP'], 400);
    }

    if (now()->gt($record->expires_at)) {
        return response()->json(['message' => 'OTP expired'], 400);
    }

    return response()->json(['message' => 'OTP verified']);
}

public function resetPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'otp' => 'required',
        'password' => [
            'required',
            'confirmed',
            'min:12',
            'regex:/[A-Z]/',
            'regex:/[a-z]/',
            'regex:/[0-9]/',
            'regex:/[^a-zA-Z0-9]/',
        ]
    ], [
        'password.min'   => 'Password must be at least 12 characters.',
        'password.regex' => 'Password must contain uppercase, lowercase, a number, and a special character.',
    ]);

    // Re-verify OTP before allowing password reset
    $record = PasswordOtp::where('email', $request->email)->first();

    if (!$record) {
        return response()->json(['message' => 'No OTP found. Please request a new one.'], 400);
    }

    if (now()->gt($record->expires_at)) {
        return response()->json(['message' => 'OTP expired. Please request a new one.'], 400);
    }

    if (!Hash::check($request->otp, $record->otp)) {
        return response()->json(['message' => 'Invalid OTP'], 400);
    }

    $user = User::where('email', $request->email)->first();
    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }
    $user->password = Hash::make($request->password);
    $user->save();

    // Delete OTP after successful reset
    PasswordOtp::where('email', $request->email)->delete();

    return response()->json(['message' => 'Password reset successful']);
}

//     public function googleLogin(Request $request)
// {
//     $request->validate([
//         'credential' => 'required'
//     ]);

//     $client = new GoogleClient([
//         'client_id' => env('GOOGLE_CLIENT_ID')
//     ]);

//     $payload = $client->verifyIdToken($request->credential);

//     if (!$payload) {
//         return response()->json(['message' => 'Invalid Google token'], 401);
//     }

//     $email = $payload['email'];
//     $name = $payload['name'];

//     // 🔥 STRICT DOMAIN CHECK
//     if (!str_ends_with($email, '@gordoncollege.edu.ph')) {
//         return response()->json([
//             'message' => 'Only Gordon College accounts are allowed'
//         ], 403);
//     }

//     // find or create user
//     $user = User::firstOrCreate(
//         ['email' => $email],
//         [
//             'name' => $name,
//             'password' => Hash::make('google-login') // dummy
//         ]
//     );

//     // create token
//     $token = $user->createToken('auth_token')->plainTextToken;

//     return response()->json([
//         'message' => 'Google login successful',
//         'token' => $token,
//         'user' => $user
//     ]);
// }
    public function updateProfile(Request $request)
    {
        $request->validate([
            'phone_number' => 'nullable|string|max:11',
            'age' => 'nullable|integer',
            'gender' => 'nullable|string|in:Male,Female,Non-binary,Prefer not to say'
        ]);

        $user = $request->user();
        $user->update([
            'phone_number' => $request->phone_number,
            'age' => $request->age,
            'gender' => $request->gender
        ]);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    public function sendChangePasswordOtp(Request $request)
    {
        $request->validate([
            'current_password' => 'required'
        ]);

        $user = $request->user();

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Incorrect current password'
            ], 401);
        }

        $otp = rand(100000, 999999);
        $expiresAt = now()->addMinutes(5);

        PasswordOtp::updateOrCreate(
            ['email' => $user->email],
            [
                'otp' => Hash::make($otp),
                'expires_at' => $expiresAt
            ]
        );

        $this->mailService->sendOtp($user->email, $otp, 'change');

        return response()->json([
            'message' => 'OTP sent to your email',
            'expires_at' => $expiresAt
        ]);
    }

    public function verifyChangePasswordOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required'
        ]);

        $user = $request->user();
        $record = PasswordOtp::where('email', $user->email)->first();

        if (!$record || !Hash::check($request->otp, $record->otp)) {
            return response()->json(['message' => 'Invalid OTP'], 400);
        }

        if (now()->gt($record->expires_at)) {
            return response()->json(['message' => 'OTP expired'], 400);
        }

        return response()->json(['message' => 'OTP verified']);
    }

    public function changePassword(Request $request)
    {
        // 1. Validate request
        $request->validate([
            'current_password' => 'required',
            'new_password' => [
                'required',
                'confirmed',
                'min:12',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[^a-zA-Z0-9]/',
            ],
            'otp' => 'required'
        ], [
            'new_password.min'   => 'New password must be at least 12 characters.',
            'new_password.regex' => 'New password must contain uppercase, lowercase, a number, and a special character.',
        ]);

        // 2. Get authenticated user
        /** @var \App\Models\User $user */
        $user = Auth::user(); 

        // 3. Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Incorrect current password'
            ], 401);
        }

        // 4. Find OTP record in PasswordOtp table
        $record = PasswordOtp::where('email', $user->email)->first();

        if (!$record) {
            Log::warning("Change password: No OTP record found for {$user->email}");
            return response()->json(['message' => 'No OTP found. Please request a new one.'], 400);
        }

        // 5. Check expiration first
        if (now()->gt($record->expires_at)) {
            Log::info("Change password: OTP expired for {$user->email}");
            return response()->json(['message' => 'OTP expired. Please request a new one.'], 400);
        }

        // 6. Verify OTP hash
        if (!Hash::check($request->otp, $record->otp)) {
            Log::warning("Change password: OTP hash mismatch for {$user->email}");
            return response()->json(['message' => 'Invalid OTP code.'], 400);
        }

        // 7. Update Password
        $user->password = Hash::make($request->new_password);
        $saved = $user->save();

        // 8. Invalidate OTP
        $record->delete();

        if (!$saved) {
            return response()->json(['message' => 'Failed to save password to database'], 500);
        }

        Log::info("Password changed successfully for {$user->email}");
        return response()->json(['message' => 'Password updated successfully']);
    }

    public function uploadProfileImage(Request $request)
    {
        $request->validate([
            'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');

            // ── Security: Block dangerous file extensions ────────
            // Even if MIME validation passes, reject known executable extensions.
            // Attackers may craft files with valid image headers but dangerous extensions.
            $dangerousExtensions = [
                'php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'phar',
                'exe', 'bat', 'cmd', 'sh', 'bash', 'com', 'vbs', 'js',
                'jar', 'py', 'pl', 'cgi', 'asp', 'aspx', 'jsp', 'svg',
            ];

            $extension = strtolower($file->getClientOriginalExtension());
            if (in_array($extension, $dangerousExtensions)) {
                Log::channel('security')->warning('Blocked dangerous file upload', [
                    'user_id'   => $user->id,
                    'extension' => $extension,
                    'filename'  => $file->getClientOriginalName(),
                ]);

                return response()->json([
                    'message' => 'This file type is not allowed.'
                ], 422);
            }

            // ── Security: Verify actual MIME type server-side ────
            // Don't trust the client-reported Content-Type. Use finfo
            // to read the file's magic bytes and determine real type.
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $realMime = $finfo->file($file->getRealPath());
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];

            if (!in_array($realMime, $allowedMimes)) {
                Log::channel('security')->warning('MIME type mismatch on upload', [
                    'user_id'       => $user->id,
                    'reported_mime' => $file->getMimeType(),
                    'actual_mime'   => $realMime,
                ]);

                return response()->json([
                    'message' => 'Invalid file type detected.'
                ], 422);
            }

            // ── Security: Double-check file size at application level
            // Belt-and-suspenders — form validation can be bypassed.
            if ($file->getSize() > 2097152) { // 2MB in bytes
                return response()->json([
                    'message' => 'File size exceeds the 2MB limit.'
                ], 422);
            }

            // Delete old image if it exists
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }

            // ── Security: Randomize filename ─────────────────────
            // Prevents filename enumeration and path traversal attacks.
            // Uses UUID + original extension for unique, safe filenames.
            $safeFilename = \Illuminate\Support\Str::uuid() . '.' . $extension;
            $path = $file->storeAs('profile_images', $safeFilename, 'public');

            $user->profile_image = $path;
            $user->save();

            $freshUser = $user->fresh();
            return response()->json([
                'message' => 'Profile image uploaded successfully',
                'user' => $freshUser,
                'profile_image_url' => $freshUser->profile_image_url
            ]);
        }

        return response()->json(['message' => 'No image provided'], 400);
    }

    /**
     * Generate and send a login verification OTP.
     * Invalidates any previous unused OTPs for the user.
     */
    private function sendLoginOtp(User $user): Carbon
    {
        // Invalidate all previous unused OTPs
        EmailOtp::where('user_id', $user->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        // Generate and store new OTP
        $otp = rand(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(5);

        EmailOtp::create([
            'user_id' => $user->id,
            'otp_code' => Hash::make($otp),
            'expires_at' => $expiresAt,
        ]);

        // Send email via API
        $this->mailService->sendOtp($user->email, $otp, 'login');

        Log::info("Login OTP sent to {$user->email}");

        return $expiresAt;
    }

    /**
     * Get the authenticated admin/guidance profile.
     */
    public function getAdminProfile(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Update the admin/guidance profile.
     */
    public function updateAdminProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|max:11',
            'unit' => 'nullable|string|in:Gordon College,Guidance Unit'
        ]);

        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'department' => $request->unit // unit maps to department
        ]);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->fresh()
        ]);
    }

    /**
     * Logout: close session log and revoke token.
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        // Close the most recent open session for this user
        $sessionLog = SessionLog::where('user_id', $user->id)
            ->whereNull('session_end')
            ->latest('session_start')
            ->first();

        if ($sessionLog) {
            $sessionLog->update(['session_end' => Carbon::now()]);
        }

        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}