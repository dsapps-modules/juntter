<?php

namespace App\Mail;

use App\Models\CheckoutSession;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CheckoutRecoveryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly CheckoutSession $checkoutSession,
        public readonly int $sequenceStep,
        public readonly string $recoveryUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->messageSubject(),
        );
    }

    public function content(): Content
    {
        $checkoutLink = $this->checkoutSession->checkoutLink;
        $product = $checkoutLink?->product;
        $seller = $checkoutLink?->seller;

        return new Content(
            markdown: 'emails.checkout-recovery',
            with: [
                'customerName' => filled($this->checkoutSession->customer_name)
                    ? (string) $this->checkoutSession->customer_name
                    : 'cliente',
                'sellerName' => filled($seller?->trade_name)
                    ? (string) $seller->trade_name
                    : (filled($seller?->name) ? (string) $seller->name : config('app.name')),
                'productName' => filled($product?->name)
                    ? (string) $product->name
                    : 'seu pedido',
                'quantity' => max(1, (int) ($this->checkoutSession->quantity ?? 1)),
                'total' => number_format((float) ($this->checkoutSession->total ?? 0), 2, ',', '.'),
                'sequenceStep' => $this->sequenceStep,
                'headline' => $this->headline(),
                'message' => $this->message(),
                'ctaLabel' => $this->ctaLabel(),
                'recoveryUrl' => $this->recoveryUrl,
            ],
        );
    }

    private function messageSubject(): string
    {
        return match ($this->sequenceStep) {
            1 => 'Seu carrinho ainda está disponível',
            2 => 'Lembrete para concluir sua compra',
            3 => 'Último lembrete do seu carrinho',
            default => 'Recuperação de carrinho',
        };
    }

    private function headline(): string
    {
        return match ($this->sequenceStep) {
            1 => 'Seu carrinho foi salvo e ainda está disponível.',
            2 => 'Ainda há tempo de concluir a compra.',
            3 => 'Este é o último lembrete antes de encerrar a recuperação.',
            default => 'Retome sua compra quando quiser.',
        };
    }

    private function message(): string
    {
        return match ($this->sequenceStep) {
            1 => 'Você deixou itens no carrinho e pode voltar exatamente de onde parou.',
            2 => 'Se houver dúvida sobre o pedido, esta é uma boa hora para finalizar a compra.',
            3 => 'O link abaixo continua válido, mas esta é a última mensagem desta sequência.',
            default => 'Você pode retomar a compra a qualquer momento.',
        };
    }

    private function ctaLabel(): string
    {
        return match ($this->sequenceStep) {
            3 => 'Finalizar agora',
            default => 'Retomar carrinho',
        };
    }
}
