<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\MoodController;

// Public (can be accessed by guests, but will attach user_id if valid auth token passed)
Route::post('/chat', [ChatController::class, 'chat']);
Route::get('/chat/history', [ChatController::class, 'history']);
Route::post('/mood', [MoodController::class, 'store']);

Route::post('/login', [AuthController::class, 'login']);

use App\Http\Controllers\ConversationController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::post('/conversations', [ConversationController::class, 'store']);
    Route::patch('/conversations/{id}', [ConversationController::class, 'update']);
    Route::delete('/conversations/{id}', [ConversationController::class, 'destroy']);
    
    Route::put('/user', [AuthController::class, 'updateProfile']);
    Route::post('/user/image', [AuthController::class, 'uploadProfileImage']);
    Route::post('/send-otp', [AuthController::class, 'sendChangePasswordOtp']);
    Route::post('/verify-otp-password', [AuthController::class, 'verifyChangePasswordOtp']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
});

Route::post('/register', [AuthController::class, 'register']);

Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

Route::post('/resend-otp', [AuthController::class, 'resendOtp']);

Route::middleware(['auth:sanctum', 'role:guidance'])->get('/users', function () {
    return \App\Models\User::all();
});

Route::post('/forgot-password/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyForgotPasswordOtp']);
Route::post('/forgot-password/reset', [AuthController::class, 'resetPassword']);

// Route::post('/google-login', [AuthController::class, 'googleLogin']);