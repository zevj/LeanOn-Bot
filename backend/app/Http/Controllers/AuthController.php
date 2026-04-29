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
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // ✅ Validate input
        $request->validate([
            'email' => [
                'required',
                'regex:/^[0-9]{9}@gordoncollege\.edu\.ph$/'
            ],
            'password' => 'required|min:6'
        ], [
            'email.regex' => 'Only Gordon College emails with 9-digit ID are allowed.'
        ]);
        
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

        // 🔐 First-time login: require email OTP verification
        if (is_null($user->email_verified_at)) {
            $token = $user->createToken('auth_token')->plainTextToken;
            $expiresAt = $this->sendLoginOtp($user);

            return response()->json([
                'status' => 'OTP_REQUIRED',
                'message' => 'Please verify your email',
                'token' => $token,
                'user' => $user,
                'expires_at' => $expiresAt
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Create session log entry
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

    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'regex:/^[0-9]{9}@gordoncollege\.edu\.ph$/',
                'unique:users,email'
            ],
            'password' => 'required|min:6|confirmed',
            'department' => 'required|string',
            'program' => 'required|string',
            'year_level' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
    
        $otp = rand(100000, 999999);
    
        $expiresAt = Carbon::now()->addMinutes(2); 
    
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'otp' => Hash::make($otp),
            'otp_expires_at' => $expiresAt,
            'role' => 'student',
            'department' => $request->department,
            'program' => $request->program,
            'year_level' => $request->year_level,
        ]);

        Mail::to($user->email)->send(new OtpMail($otp, 'register'));
    
        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'otp_expires_at' => $expiresAt
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

    // Issue fresh token
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'Email verified successfully',
        'token' => $token,
        'user' => $user
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
    $expiresAt = now()->addMinutes(2);

    PasswordOtp::updateOrCreate(
        ['email' => $request->email],
        [
            'otp' => Hash::make($otp),
            'expires_at' => $expiresAt
        ]
    );

    // ✅ SEND EMAIL
    Mail::to($request->email)->send(new OtpMail($otp, 'forgot'));

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
        'password' => 'required|min:6|confirmed'
    ]);

    $user = User::where('email', $request->email)->first();
    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }
    $user->password = Hash::make($request->password);
    $user->save();

    // delete OTP after use
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
            'phone_number' => 'nullable|string|max:11'
        ]);

        $user = $request->user();
        $user->update([
            'phone_number' => $request->phone_number
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
        $expiresAt = now()->addMinutes(2);

        PasswordOtp::updateOrCreate(
            ['email' => $user->email],
            [
                'otp' => Hash::make($otp),
                'expires_at' => $expiresAt
            ]
        );

        Mail::to($user->email)->send(new OtpMail($otp, 'forgot'));

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
            'new_password' => 'required|min:6|confirmed',
            'otp' => 'required'
        ]);

        // 2. Get authenticated user
        /** @var \App\Models\User $user */
        $user = Auth::user(); 

        // 3. Verify current password (redundant but safe)
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Incorrect current password'
            ], 401);
        }

        // 4. Find OTP record in PasswordOtp table
        $record = PasswordOtp::where('email', $user->email)->first();

        // 5. Verify OTP
        if (!$record || !Hash::check($request->otp, $record->otp)) {
            return response()->json(['message' => 'Invalid or missing OTP'], 400);
        }

        if (now()->gt($record->expires_at)) {
            return response()->json(['message' => 'OTP expired'], 400);
        }

        // 6. Update Password
        // Use Hash::make as requested by user
        $user->password = Hash::make($request->new_password);
        $saved = $user->save();

        // 7. Invalidate OTP
        $record->delete();

        if (!$saved) {
            return response()->json(['message' => 'Failed to save password to database'], 500);
        }

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
            // Delete old image if it exists
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }

            $path = $request->file('profile_image')->store('profile_images', 'public');
            $user->profile_image = $path;
            $user->save();

            return response()->json([
                'message' => 'Profile image uploaded successfully',
                'user' => $user->fresh() // Returns the user with the profile_image_url append
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

        // Send email
        Mail::to($user->email)->send(new OtpMail($otp, 'login'));

        Log::info("Login OTP sent to {$user->email}");

        return $expiresAt;
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