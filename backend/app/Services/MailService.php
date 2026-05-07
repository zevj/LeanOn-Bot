<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class MailService
{
    /**
     * Send OTP email using Brevo HTTP API.
     */
    public function sendOtp(string $toEmail, int|string $otp, string $type)
    {
        $apiKey = env('BREVO_API_KEY');

        if (!$apiKey) {
            Log::error('Brevo API Key is missing in environment variables.');
            throw new \Exception('Email service is not configured properly. Please add BREVO_API_KEY.');
        }

        $fromEmail = env('MAIL_FROM_ADDRESS', 'leanonbot3@gmail.com');
        $fromName = env('MAIL_FROM_NAME', 'LeanOn Bot');

        // Render the email view to HTML
        $htmlContent = View::make('emails.otp', [
            'otp' => $otp,
            'type' => $type
        ])->render();

        $response = Http::withHeaders([
            'api-key' => $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => [
                'name' => $fromName,
                'email' => $fromEmail,
            ],
            'to' => [
                [
                    'email' => $toEmail,
                ]
            ],
            'subject' => 'LeanOn Bot OTP Verification',
            'htmlContent' => $htmlContent,
        ]);

        if ($response->successful()) {
            Log::info("Email sent successfully via Brevo API to: {$toEmail}");
            return true;
        }

        Log::error("Failed to send email via Brevo API: " . $response->body());
        throw new \Exception('Failed to send OTP email.');
    }
}
