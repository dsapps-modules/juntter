<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecorrenciaLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $recipientName,
        public readonly string $paymentType,
        public readonly string $amount,
        public readonly string $frequency,
        public readonly string $paymentLinkUrl,
        public readonly string $emailMessage,
        public readonly ?string $phoneNumber = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Cobrança recorrente - '.$this->paymentTypeLabel(),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.recorrencia-link',
            with: [
                'recipientName' => $this->recipientName,
                'paymentType' => $this->paymentType,
                'amount' => $this->amount,
                'frequency' => $this->frequency,
                'paymentLinkUrl' => $this->paymentLinkUrl,
                'emailMessage' => $this->emailMessage,
                'phoneNumber' => $this->phoneNumber,
                'paymentTypeLabel' => $this->paymentTypeLabel(),
            ],
        );
    }

    private function paymentTypeLabel(): string
    {
        return match ($this->paymentType) {
            'PIX' => 'Pix',
            'BOLETO' => 'Boleto',
            'CARTAO' => 'Cartão de Crédito',
            default => $this->paymentType,
        };
    }
}
