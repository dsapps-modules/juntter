<?php

namespace App\Services\Payments\Paytime;

use App\Models\CheckoutLink;
use App\Models\CheckoutSession;
use App\Models\Order;
use App\Services\ApiClientService;
use App\Services\BoletoService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class PaytimeClient
{
    public function __construct(
        private readonly ApiClientService $apiClient,
        private readonly BoletoService $boletoService,
    ) {}

    public function createPixPayment(Order $order): array
    {
        $order->loadMissing('seller.vendedor');

        $payload = $this->buildPixPayload($order);
        $transaction = $this->apiClient->post('marketplace/transactions', $payload);
        $transactionId = $this->resolveTransactionId($transaction);

        Log::info('Paytime Pix transaction response received', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'transaction_id' => $transactionId,
            'transaction_keys' => array_keys($transaction),
            'transaction_payload' => $transaction,
        ]);

        if ($transactionId === null) {
            Log::warning('Paytime Pix transaction response did not include a transaction id', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'transaction_payload' => $transaction,
            ]);

            return $transaction;
        }

        $qrCode = [];

        try {
            $qrCode = $this->apiClient->get("marketplace/transactions/{$transactionId}/qrcode");
        } catch (\Throwable $throwable) {
            $qrCode = [];
        }

        Log::info('Paytime Pix qrcode response received', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'transaction_id' => $transactionId,
            'qrcode_keys' => array_keys($qrCode),
            'qrcode_payload' => $qrCode,
        ]);

        $pixCode = $qrCode['emv'] ?? $transaction['emv'] ?? null;

        return [
            'gateway_transaction_id' => $transactionId,
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
        $order->loadMissing(['seller.vendedor', 'checkoutLink', 'checkoutSession']);

        $payload = $this->buildBoletoPayload($order);
        $boleto = $this->boletoService->gerarBoletoComConsulta($payload);
        $transactionId = $this->resolveTransactionId($boleto);

        Log::info('Paytime Boleto response received', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'transaction_id' => $transactionId,
            'boleto_keys' => array_keys($boleto),
            'boleto_payload' => $boleto,
        ]);

        if ($transactionId === null) {
            Log::warning('Paytime boleto response did not include a transaction id', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'boleto_payload' => $boleto,
            ]);
        }

        return [
            'gateway_transaction_id' => $transactionId,
            'gateway_status' => $this->normalizeGatewayStatus($boleto['status'] ?? null),
            'internal_status' => $this->normalizeBoletoInternalStatus($boleto['status'] ?? null),
            'boleto_url' => $boleto['boleto_url'] ?? null,
            'boleto_barcode' => $boleto['boleto_barcode'] ?? null,
            'boleto_digitable_line' => $boleto['boleto_digitable_line'] ?? null,
            'api_boleto' => $boleto,
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
            'interest' => $this->resolveInterest(),
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
     * @return array{
     *     amount: int,
     *     expiration: string,
     *     payment_limit_date: string,
     *     recharge: bool,
     *     client: array{
     *         first_name: string,
     *         last_name: string,
     *         email: string,
     *         phone: string,
     *         document: string,
     *         address: array{
     *             street: string,
     *             number: string,
     *             complement: string,
     *             neighborhood: string,
     *             city: string,
     *             state: string,
     *             zip_code: string
     *         }
     *     },
     *     instruction: array{
     *         booklet: bool,
     *         description: string,
     *         late_fee: array{mode: string, amount: float},
     *         interest: array{mode: string, amount: float},
     *         discount: array{mode: string, amount: float, limit_date: string}
     *     },
     *     extra_headers: array{establishment_id: string|int}
     * }
     */
    private function buildBoletoPayload(Order $order): array
    {
        $customerName = trim((string) $order->customer_name);
        [$firstName, $lastName] = $this->splitName($customerName);
        $establishmentId = $this->resolveEstablishmentId($order);
        $checkoutSession = $order->checkoutSession;
        $checkoutLink = $order->checkoutLink;
        $expiration = now()->addDays(7)->format('Y-m-d');
        $paymentLimitDate = now()->addDays(8)->format('Y-m-d');
        $discountLimitDate = now()->addDays(5)->format('Y-m-d');

        if ($checkoutLink instanceof CheckoutLink && $checkoutLink->expires_at !== null) {
            $expiration = $checkoutLink->expires_at->format('Y-m-d');
        }

        if ($checkoutSession instanceof CheckoutSession && filled($checkoutSession->last_activity_at)) {
            $paymentLimitDate = $checkoutSession->last_activity_at->copy()->addDays(1)->format('Y-m-d');
        }

        return [
            'amount' => $this->toCents($order->total),
            'expiration' => $expiration,
            'payment_limit_date' => $paymentLimitDate,
            'recharge' => false,
            'client' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => (string) $order->customer_email,
                'phone' => $this->normalizeDigits((string) $order->customer_phone),
                'document' => $this->normalizeDigits((string) $order->customer_document),
                'address' => [
                    'street' => (string) ($checkoutSession->street ?? ''),
                    'number' => (string) ($checkoutSession->number ?? ''),
                    'complement' => (string) ($checkoutSession->complement ?? ''),
                    'neighborhood' => (string) ($checkoutSession->neighborhood ?? ''),
                    'city' => (string) ($checkoutSession->city ?? ''),
                    'state' => (string) ($checkoutSession->state ?? ''),
                    'zip_code' => $this->normalizeDigits((string) ($checkoutSession->zipcode ?? '')),
                ],
            ],
            'instruction' => [
                'booklet' => false,
                'description' => (string) ($checkoutLink?->name ?? $order->order_number),
                'late_fee' => [
                    'mode' => 'PERCENTAGE',
                    'amount' => 2.0,
                ],
                'interest' => [
                    'mode' => 'MONTHLY_PERCENTAGE',
                    'amount' => 1.0,
                ],
                'discount' => [
                    'mode' => 'PERCENTAGE',
                    'amount' => 5.0,
                    'limit_date' => $discountLimitDate,
                ],
            ],
            'extra_headers' => [
                'establishment_id' => $establishmentId,
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

    private function normalizeBoletoInternalStatus(mixed $status): string
    {
        $normalized = strtoupper(trim((string) $status));

        return in_array($normalized, ['PAID', 'APPROVED'], true) ? 'paid' : 'pending';
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

    private function resolveTransactionId(array $transaction): ?string
    {
        $transactionId = $transaction['_id']
            ?? $transaction['gateway_transaction_id']
            ?? $transaction['transaction_id']
            ?? $transaction['id']
            ?? null;

        if (! is_scalar($transactionId) || trim((string) $transactionId) === '') {
            return null;
        }

        return (string) $transactionId;
    }

    private function resolveInterest(): string
    {
        return 'CLIENT';
    }
}
