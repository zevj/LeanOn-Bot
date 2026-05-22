<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\MoodController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\CrisisAlertController;
use App\Http\Controllers\EmotionController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\AnalyticsController;

// ── Public Routes (rate-limited) ──────────────────────────────────
// These endpoints are accessible without authentication but are
// protected by per-endpoint rate limiting to prevent abuse.

Route::middleware('throttle:chat')->group(function () {
    Route::post('/chat', [ChatController::class, 'chat']);
});
Route::get('/chat/history', [ChatController::class, 'history']);
Route::post('/mood', [MoodController::class, 'store']);

// Login: tight rate limit to prevent brute-force attacks
Route::middleware('throttle:login')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// ── Authenticated Routes ──────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/terms/current', [App\Http\Controllers\TermsController::class, 'current']);
    Route::post('/terms/accept', [App\Http\Controllers\TermsController::class, 'accept']);

    // Routes requiring terms acceptance
    Route::middleware(\App\Http\Middleware\EnsureTermsAccepted::class)->group(function () {
        Route::get('/conversations', [ConversationController::class, 'index']);
        Route::post('/conversations', [ConversationController::class, 'store']);
        Route::patch('/conversations/{id}', [ConversationController::class, 'update']);
        Route::delete('/conversations/{id}', [ConversationController::class, 'destroy']);

        Route::put('/user', [AuthController::class, 'updateProfile']);

        // File upload: separate rate limit to prevent storage abuse
        Route::middleware('throttle:upload')->group(function () {
            Route::post('/user/image', [AuthController::class, 'uploadProfileImage']);
        });

        // Authenticated OTP & password change: use OTP rate limit
        Route::middleware('throttle:otp')->group(function () {
            Route::post('/send-otp', [AuthController::class, 'sendChangePasswordOtp']);
            Route::post('/verify-otp-password', [AuthController::class, 'verifyChangePasswordOtp']);
        });
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });

    Route::post('/logout', [AuthController::class, 'logout']);
});

// ── OTP Verification Routes (rate-limited) ────────────────────────
Route::middleware('throttle:otp')->group(function () {
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('/login-otp/resend', [AuthController::class, 'resendOtp']);
});

// ── Admin: list all users ─────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:guidance'])->get('/users', function () {
    return \App\Models\User::all();
});

// ── Password Reset Routes (rate-limited) ──────────────────────────
Route::middleware('throttle:password-reset')->group(function () {
    Route::post('/forgot-password/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyForgotPasswordOtp']);
    Route::post('/forgot-password/reset', [AuthController::class, 'resetPassword']);
});

// ── Admin Panel API Routes ────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:guidance'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);
    Route::get('/crisis-alerts', [CrisisAlertController::class, 'index']);
    Route::patch('/crisis-alerts/{id}', [CrisisAlertController::class, 'update']);
    Route::get('/emotional-trends', [EmotionController::class, 'index']);
    Route::get('/logs', [LogController::class, 'index']);

    // Admin Profile Routes
    Route::get('/profile', [AuthController::class, 'getAdminProfile']);
    Route::put('/profile', [AuthController::class, 'updateAdminProfile']);
    Route::post('/profile/image', [AuthController::class, 'uploadProfileImage']);

    // ── AI Analytics & Insights ───────────────────────────────
    Route::get('/analytics/dashboard', [AnalyticsController::class, 'dashboard']);
    Route::get('/analytics/trends', [AnalyticsController::class, 'trends']);
    Route::get('/analytics/insights', [AnalyticsController::class, 'insights']);
    Route::get('/analytics/wellness-report', [AnalyticsController::class, 'wellnessReport']);
    Route::get('/analytics/snapshots', [AnalyticsController::class, 'snapshots']);
});

// ── Google Auth Routes ────────────────────────────────────────────
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);
