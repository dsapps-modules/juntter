<?php

namespace App\Services\Payments\Paytime;

use App\Models\Order;
use Illuminate\Support\Str;

class PaytimeClient
{
    public function createPixPayment(Order $order): array
    {
        $transactionId = 'ptx_'.Str::random(20);

        return [
            'gateway_transaction_id' => $transactionId,
            'gateway_status' => 'pending',
            'internal_status' => 'pending',
            'pix_qr_code' => '000201'.Str::random(40),
            'pix_copy_paste' => '000201'.Str::random(48),
            'pix_expires_at' => now()->addHours(2),
        ];
    }

    public function createBoletoPayment(Order $order): array
    {
        $transactionId = 'ptb_'.Str::random(20);

        return [
            'gateway_transaction_id' => $transactionId,
            'gateway_status' => 'pending',
            'internal_status' => 'pending',
            'boleto_url' => url('/boleto/'.$transactionId),
            'boleto_barcode' => Str::random(44),
            'boleto_digitable_line' => Str::random(47),
        ];
    }

    public function createCreditCardPayment(Order $order, array $cardData): array
    {
        $transactionId = 'ptc_'.Str::random(20);

        return [
            'gateway_transaction_id' => $transactionId,
            'gateway_status' => 'authorized',
            'internal_status' => 'authorized',
            'card_last_four' => $cardData['card_last_four'] ?? null,
            'card_brand' => $cardData['card_brand'] ?? null,
        ];
    }

    public function parseWebhook(array $payload, array $headers): array
    {
        return [
            'payload' => $payload,
            'headers' => $headers,
        ];
    }
}
