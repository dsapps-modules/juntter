<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SecurityCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly string $purpose,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Código de verificação - '.$this->purpose,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.security-code',
            with: [
                'code' => $this->code,
                'purpose' => $this->purpose,
            ],
        );
    }
}
