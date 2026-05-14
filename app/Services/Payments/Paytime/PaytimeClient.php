<?php

namespace App\Services\Payments\Paytime;

use App\Models\CheckoutLink;
use App\Models\CheckoutSession;
use App\Models\Order;
use App\Services\ApiClientService;
use App\Services\BoletoService;
use Illuminate\Support\Facades\Log;
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

    public function refreshBoletoPayment(string $gatewayTransactionId): array
    {
        $boleto = $this->boletoService->normalizarResposta(
            $this->boletoService->consultarBoleto($gatewayTransactionId)
        );

        if ($this->isBoletoErrorResponse($boleto)) {
            return [
                'gateway_transaction_id' => $gatewayTransactionId,
                'gateway_status' => 'FAILED',
                'internal_status' => 'failed',
                'boleto_url' => null,
                'boleto_barcode' => null,
                'boleto_digitable_line' => null,
                'polling_error' => $boleto['message'] ?? 'Não foi possível gerar o boleto.',
                'api_boleto' => $boleto,
            ];
        }

        return [
            'gateway_transaction_id' => $gatewayTransactionId,
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
        $order->loadMissing(['seller.vendedor', 'checkoutSession', 'checkoutLink']);
        $payload = $this->buildCreditCardPayload($order, $cardData);
        $transaction = $this->apiClient->post('marketplace/transactions', $payload);
        $transactionId = $this->resolveTransactionId($transaction);
        $requires3ds = $this->requiresCreditCard3ds($transaction);

        Log::info('Paytime credit card transaction response received', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'transaction_id' => $transactionId,
            'transaction_keys' => array_keys($transaction),
            'transaction_payload' => $transaction,
        ]);

        if ($transactionId === null) {
            Log::warning('Paytime credit card transaction response did not include a transaction id', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'transaction_payload' => $transaction,
            ]);
        }

        return [
            'gateway_transaction_id' => $transactionId,
            'gateway_status' => $this->normalizeGatewayStatus($transaction['status'] ?? null),
            'internal_status' => $requires3ds
                ? 'pending'
                : $this->normalizeCreditCardInternalStatus($transaction['status'] ?? null),
            'card_last_four' => $this->resolveCardLastFour($transaction, $cardData),
            'card_brand' => $this->resolveCardBrand($transaction, $cardData),
            'installments' => $this->resolveCreditCardInstallments($transaction, $cardData),
            'api_transaction' => $transaction,
            'requires_3ds' => $requires3ds,
            'session_id' => $requires3ds ? $this->resolveCreditCard3dsSessionId($transaction) : null,
            'transaction_id' => $transactionId,
            'message' => $requires3ds
                ? 'Transação criada, aguardando autenticação 3DS.'
                : 'Transação criada com sucesso.',
        ];
    }

    public function confirmCreditCard3ds(string $gatewayTransactionId, array $authData): array
    {
        $transaction = $this->apiClient->post("marketplace/transactions/{$gatewayTransactionId}/antifraud-auth", $authData);

        Log::info('Paytime credit card 3DS confirmation response received', [
            'transaction_id' => $gatewayTransactionId,
            'transaction_keys' => array_keys($transaction),
            'transaction_payload' => $transaction,
        ]);

        return [
            'gateway_transaction_id' => $this->resolveTransactionId($transaction) ?? $gatewayTransactionId,
            'gateway_status' => $this->normalizeGatewayStatus($transaction['status'] ?? null),
            'internal_status' => $this->normalizeCreditCardInternalStatus($transaction['status'] ?? null),
            'api_transaction' => $transaction,
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
        $expirationDate = now()->addDays(7)->startOfDay();
        $paymentLimitDate = now()->addDays(8)->startOfDay();
        $discountLimitDate = now()->addDays(5)->startOfDay();

        if ($checkoutLink instanceof CheckoutLink && $checkoutLink->expires_at !== null) {
            $expirationDate = $checkoutLink->expires_at->copy()->startOfDay();
        }

        if ($checkoutSession instanceof CheckoutSession && filled($checkoutSession->last_activity_at)) {
            $paymentLimitDate = $checkoutSession->last_activity_at->copy()->addDay()->startOfDay();
        }

        if ($paymentLimitDate->lessThanOrEqualTo($expirationDate)) {
            $paymentLimitDate = $expirationDate->copy()->addDay();
        }

        return [
            'amount' => $this->toCents($order->total),
            'expiration' => $expirationDate->format('Y-m-d'),
            'payment_limit_date' => $paymentLimitDate->format('Y-m-d'),
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
                    'limit_date' => $discountLimitDate->format('Y-m-d'),
                ],
            ],
            'extra_headers' => [
                'establishment_id' => $establishmentId,
            ],
        ];
    }

    private function buildCreditCardPayload(Order $order, array $cardData): array
    {
        $customerName = trim((string) $order->customer_name);
        [$firstName, $lastName] = $this->splitName($customerName);
        $establishmentId = $this->resolveEstablishmentId($order);
        $checkoutSession = $order->checkoutSession;
        $phoneParts = $this->resolvePhoneParts((string) $order->customer_phone);
        $address = [
            'street' => (string) ($checkoutSession?->street ?? ''),
            'number' => (string) ($checkoutSession?->number ?? ''),
            'complement' => (string) ($checkoutSession?->complement ?? ''),
            'neighborhood' => (string) ($checkoutSession?->neighborhood ?? ''),
            'city' => (string) ($checkoutSession?->city ?? ''),
            'state' => (string) ($checkoutSession?->state ?? ''),
            'zip_code' => $this->normalizeDigits((string) ($checkoutSession?->zipcode ?? '')),
        ];
        $card = $cardData['card'] ?? [];
        $installments = (int) ($cardData['installments'] ?? 1);

        return [
            'payment_type' => 'CREDIT',
            'amount' => $this->toCents($order->total),
            'installments' => $installments > 0 ? $installments : 1,
            'interest' => $this->resolveInterest(),
            'client' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => (string) $order->customer_email,
                'phone' => $phoneParts['number'],
                'phones' => [
                    [
                        'country' => '55',
                        'area' => $phoneParts['area'],
                        'number' => $phoneParts['number'],
                        'type' => 'MOBILE',
                    ],
                ],
                'document' => $this->normalizeDigits((string) ($card['holder_document'] ?? $order->customer_document)),
                'address' => $address,
            ],
            'card' => [
                'holder_name' => trim((string) ($card['holder_name'] ?? $order->customer_name)),
                'holder_document' => $this->normalizeDigits((string) ($card['holder_document'] ?? $order->customer_document)),
                'card_number' => $this->normalizeDigits((string) ($card['card_number'] ?? '')),
                'expiration_month' => (int) ($card['expiration_month'] ?? 0),
                'expiration_year' => (int) ($card['expiration_year'] ?? 0),
                'security_code' => $this->normalizeDigits((string) ($card['security_code'] ?? '')),
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

    private function normalizeCreditCardInternalStatus(mixed $status): string
    {
        $normalized = strtoupper(trim((string) $status));

        if (in_array($normalized, ['PAID', 'APPROVED', 'CONFIRMED', 'SUCCESS'], true)) {
            return 'paid';
        }

        return $normalized !== '' ? strtolower($normalized) : 'pending';
    }

    private function normalizeBoletoInternalStatus(mixed $status): string
    {
        $normalized = strtoupper(trim((string) $status));

        return in_array($normalized, ['PAID', 'APPROVED'], true) ? 'paid' : 'pending';
    }

    private function isBoletoErrorResponse(array $boleto): bool
    {
        $status = $boleto['status'] ?? null;

        return isset($boleto['message'])
            && (is_int($status) || (is_string($status) && ctype_digit($status)))
            && (int) $status >= 400;
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
            ?? data_get($transaction, 'transaction._id')
            ?? data_get($transaction, 'transaction.id')
            ?? data_get($transaction, 'transaction.transaction_id')
            ?? data_get($transaction, 'data.transaction._id')
            ?? data_get($transaction, 'data.transaction.id')
            ?? data_get($transaction, 'data.transaction.transaction_id')
            ?? data_get($transaction, 'data.result._id')
            ?? data_get($transaction, 'data.result.id')
            ?? data_get($transaction, 'data.response._id')
            ?? data_get($transaction, 'data.response.id')
            ?? data_get($transaction, 'api_boleto._id')
            ?? data_get($transaction, 'api_boleto.id')
            ?? data_get($transaction, 'data._id')
            ?? data_get($transaction, 'data.id')
            ?? null;

        if (! is_scalar($transactionId) || trim((string) $transactionId) === '') {
            return null;
        }

        return (string) $transactionId;
    }

    private function resolveCardBrand(array $transaction, array $cardData): ?string
    {
        $brand = data_get($transaction, 'card.brand_name')
            ?? data_get($transaction, 'card.brand')
            ?? data_get($transaction, 'brand_name')
            ?? data_get($transaction, 'brand')
            ?? $cardData['card_brand']
            ?? null;

        if (! is_scalar($brand) || trim((string) $brand) === '') {
            return null;
        }

        return strtoupper(trim((string) $brand));
    }

    private function resolveCardLastFour(array $transaction, array $cardData): ?string
    {
        $lastFour = data_get($transaction, 'card.last4_digits')
            ?? data_get($transaction, 'card.last4')
            ?? data_get($transaction, 'last4_digits')
            ?? data_get($transaction, 'last4')
            ?? $cardData['card_last_four']
            ?? null;

        if (! is_scalar($lastFour)) {
            return null;
        }

        $normalized = $this->normalizeDigits((string) $lastFour);

        return $normalized !== '' ? str_pad(substr($normalized, -4), 4, '0', STR_PAD_LEFT) : null;
    }

    private function resolveCreditCardInstallments(array $transaction, array $cardData): int
    {
        $installments = data_get($transaction, 'installments') ?? $cardData['installments'] ?? 1;

        return max(1, (int) $installments);
    }

    private function requiresCreditCard3ds(array $transaction): bool
    {
        return strtoupper((string) data_get($transaction, 'antifraud.0.analyse_required')) === 'THREEDS'
            && strtoupper((string) data_get($transaction, 'antifraud.0.analyse_status')) === 'WAITING_AUTH';
    }

    private function resolveCreditCard3dsSessionId(array $transaction): ?string
    {
        $sessionId = data_get($transaction, 'antifraud.0.session');

        if (! is_scalar($sessionId) || trim((string) $sessionId) === '') {
            return null;
        }

        return (string) $sessionId;
    }

    private function resolveInterest(): string
    {
        return 'CLIENT';
    }

    /**
     * @return array{area: string, number: string}
     */
    private function resolvePhoneParts(string $phone): array
    {
        $digits = $this->normalizeDigits($phone);

        if ($digits === '') {
            return [
                'area' => '00',
                'number' => '',
            ];
        }

        if (str_starts_with($digits, '55') && strlen($digits) > 11) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) > 9) {
            return [
                'area' => substr($digits, 0, 2),
                'number' => substr($digits, 2, 9),
            ];
        }

        return [
            'area' => '00',
            'number' => substr($digits, 0, 9),
        ];
    }
}
