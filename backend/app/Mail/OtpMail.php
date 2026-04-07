<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $type;

    public function __construct($otp, $type)
    {
        $this->otp = $otp;
        $this->type = $type;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'LeanOn Bot OTP Verification',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp', // ✅ your blade file
        );
    }

    public function attachments(): array
    {
        return [];
    }
}