<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly string $purpose,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                address: (string) config('mail.from.address'),
                name: 'J&G Audio Lights and Sounds',
            ),
            subject: $this->purpose === 'account registration'
                ? 'Verify your email address | J&G Audio Lights and Sounds'
                : 'Password reset code | J&G Audio Lights and Sounds',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verification-code',
        );
    }
}
