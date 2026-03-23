<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
// use Google\Client as GoogleClient;
use App\Models\User;
use App\Models\PasswordOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

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
        $user = Auth::user();

        if (is_null($user->email_verified_at)) {
            return response()->json([
                'message' => 'Please verify your email first'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user
        ]);
    }

    public function register(Request $request) {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'regex:/^[0-9]{9}@gordoncollege\.edu\.ph$/',
                'unique:users,email'
            ],
            'password' => 'required|min:6|confirmed'
        ], [
            'email.regex' => 'Only Gordon College emails with 9-digit ID are allowed.'
        ]);
    
        $otp = rand(100000, 999999);
    
        $expiresAt = Carbon::now()->addMinutes(5); // ✅ IMPORTANT
    
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'otp' => $otp,
            'otp_expires_at' => $expiresAt,
            'role' => 'student',
        ]);
    
        Mail::raw("Your OTP is: $otp", function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('LeanOn Bot OTP Verification');
        });
    
        $token = $user->createToken('auth_token')->plainTextToken;
    
        return response()->json([
            'message' => 'User registered successfully',
            'token' => $token,
            'user' => $user,
            'otp_expires_at' => $expiresAt // 🔥 REQUIRED FOR TIMER
        ]);
    }

public function verifyOtp(Request $request)
{
    // ✅ Validate input
    $request->validate([
        'email' => 'required|email',
        'otp' => 'required'
    ]);

    // ✅ Find user
    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    Log::info('OTP DEBUG', [
        'input' => $request->otp,
        'stored' => $user->otp
    ]);

    // ✅ Check OTP match (FIXED)
    if ($user->otp != $request->otp) {
        return response()->json([
            'message' => 'Invalid OTP'
        ], 400);
    }

    // ✅ Check expiration
    if (now()->gt($user->otp_expires_at)) {
        return response()->json([
            'message' => 'OTP expired'
        ], 400);
    }

    // ✅ Mark as verified (FIXED COLUMN)
    $user->update([
        'email_verified_at' => now(),
        'otp' => null,
        'otp_expires_at' => null,
    ]);

    $user->refresh();
    
    // 🔥 BONUS: Auto-login user after verification
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'Account verified successfully',
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

    // prevent resend if already verified
    if (!is_null($user->email_verified_at)) {
        return response()->json([
            'message' => 'Account already verified'
        ], 400);
    }

    // Generate new OTP
    $otp = rand(100000, 999999);
    $expiresAt = Carbon::now()->addMinutes(2);
    $user->update([
        'otp' => $otp,
        'otp_expires_at' => now()->addMinutes(5),
    ]);

    // Send email
    Mail::raw("Your new OTP is: $otp", function ($message) use ($user) {
        $message->to($user->email)
                ->subject('LeanOn Bot - Resend OTP');
    });

    return response()->json([
        'message' => 'OTP resent successfully' , 'expires_at' => $expiresAt
    ]);
}

public function sendOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email'
    ]);

    $otp = rand(100000, 999999);
    $expiresAt = Carbon::now()->addMinutes(2);
    PasswordOtp::updateOrCreate(
        ['email' => $request->email],
        [
            'otp' => $otp,
            'expires_at' => Carbon::now()->addMinutes(5)
        ]
    );

    Mail::raw("Your OTP is: $otp", function ($message) use ($request) {
        $message->to($request->email)
                ->subject('LeanOn Bot Password Reset OTP');
    });

    return response()->json(['message' => 'OTP sent', 'expires_at' => $expiresAt]);
}

public function verifyForgotPasswordOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'otp' => 'required'
    ]);

    $record = PasswordOtp::where('email', $request->email)
        ->where('otp', $request->otp)
        ->first();

    if (!$record) {
        return response()->json(['message' => 'Invalid OTP'], 400);
    }

    if (Carbon::now()->gt($record->expires_at)) {
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
}