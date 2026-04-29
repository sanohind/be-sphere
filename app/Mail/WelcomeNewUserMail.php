<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeNewUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $setPasswordUrl;
    public string $logoUrl;

    public function __construct(
        public readonly User $user,
        public readonly string $token
    ) {
        $feUrl = rtrim(env('FE_SPHERE_URL', 'http://localhost:5173'), '/');
        $this->setPasswordUrl = $feUrl . '/#/set-password?token=' . $token;
        $this->logoUrl = rtrim(config('app.url'), '/') . '/images/logo/sphere-logo-mini.png';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Activate Your SPHERE Account',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome_new_user',
        );
    }
}