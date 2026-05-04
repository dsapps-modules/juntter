<?php

namespace App\Services\Payments\Paytime;

use App\Models\Order;
use App\Services\ApiClientService;
use Illuminate\Support\Str;
use RuntimeException;

class PaytimeClient
{
    public function __construct(private readonly ApiClientService $apiClient) {}

    public function createPixPayment(Order $order): array
    {
        $order->loadMissing('seller.vendedor');

        $payload = $this->buildPixPayload($order);
        $transaction = $this->apiClient->post('marketplace/transactions', $payload);

        if (! isset($transaction['_id'])) {
            return $transaction;
        }

        $qrCode = [];

        try {
            $qrCode = $this->apiClient->get("marketplace/transactions/{$transaction['_id']}/qrcode");
        } catch (\Throwable $throwable) {
            $qrCode = [];
        }

        $pixCode = $qrCode['emv'] ?? $transaction['emv'] ?? null;

        return [
            'gateway_transaction_id' => $transaction['_id'],
            'gateway_status' => $this->normalizeGatewayStatus($transaction['status'] ?? null),
            'internal_status' => $this->normalizeInternalStatus($transaction['status'] ?? null),
            'pix_qr_code' => $pixCode,
            'pix_copy_paste' => $pixCode,
            'pix_qr_code_image' => $qrCode['qrcode'] ?? null,
            'pix_expires_at' => $this->resolvePixExpiration($transaction),
            'api_transaction' => $transaction,
            'api_qrcode' => $qrCode ?: null,
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

    /**
     * @return array{
     *     payment_type: string,
     *     amount: int,
     *     client: array{first_name: string, last_name: string, email: string, phone: string, document: string},
     *     extra_headers: array{establishment_id: string|int},
     *     session_id: string,
     *     info_additional: array<int, array{key: string, value: string}>
     * }
     */
    private function buildPixPayload(Order $order): array
    {
        $customerName = trim((string) $order->customer_name);
        [$firstName, $lastName] = $this->splitName($customerName);
        $establishmentId = $this->resolveEstablishmentId($order);

        return [
            'payment_type' => 'PIX',
            'amount' => $this->toCents($order->total),
            'client' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => (string) $order->customer_email,
                'phone' => $this->normalizeDigits((string) $order->customer_phone),
                'document' => $this->normalizeDigits((string) $order->customer_document),
            ],
            'extra_headers' => [
                'establishment_id' => $establishmentId,
            ],
            'session_id' => 'checkout_'.($order->checkout_session_id ?? $order->id),
            'info_additional' => [
                [
                    'key' => 'order_number',
                    'value' => (string) $order->order_number,
                ],
                [
                    'key' => 'checkout_link_id',
                    'value' => (string) $order->checkout_link_id,
                ],
                [
                    'key' => 'checkout_session_id',
                    'value' => (string) $order->checkout_session_id,
                ],
            ],
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        if ($parts === []) {
            return ['Cliente', ''];
        }

        $firstName = array_shift($parts) ?: 'Cliente';
        $lastName = trim(implode(' ', $parts));

        return [$firstName, $lastName];
    }

    private function resolveEstablishmentId(Order $order): string|int
    {
        $establishmentId = $order->seller?->vendedor?->estabelecimento_id;

        if ($establishmentId === null || $establishmentId === '') {
            throw new RuntimeException('Não foi possível identificar o estabelecimento do vendedor para gerar o Pix.');
        }

        return $establishmentId;
    }

    private function toCents(float|string|int $value): int
    {
        return (int) round(((float) $value) * 100);
    }

    private function normalizeDigits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    private function normalizeGatewayStatus(mixed $status): string
    {
        $normalized = strtoupper(trim((string) $status));

        return $normalized !== '' ? $normalized : 'PENDING';
    }

    private function normalizeInternalStatus(mixed $status): string
    {
        $normalized = strtolower(trim((string) $status));

        return $normalized !== '' ? $normalized : 'pending';
    }

    private function resolvePixExpiration(array $transaction): ?string
    {
        $expiresAt = $transaction['expected_on']
            ?? $transaction['expires_at']
            ?? $transaction['pix_expires_at']
            ?? null;

        if (! is_string($expiresAt) || trim($expiresAt) === '') {
            return null;
        }

        return $expiresAt;
    }
}
