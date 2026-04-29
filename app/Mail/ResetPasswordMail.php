<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $resetPasswordUrl;
    public string $logoUrl;

    public function __construct(
        public readonly User $user,
        public readonly string $token
    ) {
        $feUrl = rtrim(env('FE_SPHERE_URL', 'http://localhost:5173'), '/');
        $this->resetPasswordUrl = $feUrl . '/#/reset-password?token=' . $token;
        $this->logoUrl = rtrim(config('app.url'), '/') . '/images/logo/sphere-logo-mini.png';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Your SPHERE Password',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reset_password',
        );
    }
}
