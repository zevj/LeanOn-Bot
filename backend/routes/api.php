<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
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