<?php

namespace Tests\Feature\Services;

use App\Models\CheckoutLink;
use App\Models\CheckoutSession;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\ApiClientService;
use App\Services\BoletoService;
use App\Services\Payments\Paytime\PaytimeClient;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PaytimeClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_pix_payment_uses_the_api_and_normalizes_the_response(): void
    {
        Log::spy();

        $seller = $this->makeVendorUser('127700');
        $product = $this->makeProduct($seller);
        $checkoutLink = $this->makeCheckoutLink($seller, $product);
        $checkoutSession = $this->makeCheckoutSession($checkoutLink);
        $order = $this->makeOrder($seller, $checkoutLink, $checkoutSession, $product);

        $apiClient = $this->createMock(ApiClientService::class);
        $apiClient->expects($this->once())
            ->method('post')
            ->with(
                'marketplace/transactions',
                $this->callback(function (array $payload) use ($order): bool {
                    return $payload['payment_type'] === 'PIX'
                        && $payload['amount'] === 15000
                        && $payload['interest'] === 'CLIENT'
                        && $payload['client']['first_name'] === 'Maria'
                        && $payload['client']['last_name'] === 'Silva'
                        && $payload['client']['email'] === 'maria@example.com'
                        && $payload['client']['phone'] === '11999999999'
                        && $payload['client']['document'] === '12345678909'
                        && $payload['extra_headers']['establishment_id'] === '127700'
                        && $payload['session_id'] === 'checkout_'.$order->checkout_session_id
                        && $payload['info_additional'][0]['value'] === $order->order_number
                        && $payload['info_additional'][1]['value'] === (string) $order->checkout_link_id
                        && $payload['info_additional'][2]['value'] === (string) $order->checkout_session_id;
                }),
            )
            ->willReturn([
                '_id' => 'pix-api-123',
                'status' => 'PENDING',
                'expected_on' => '2026-05-04T12:30:00Z',
                'emv' => '00020126580014br.gov.bcb.pix123',
            ]);

        $apiClient->expects($this->once())
            ->method('get')
            ->with('marketplace/transactions/pix-api-123/qrcode')
            ->willReturn([
                'qrcode' => 'data:image/png;base64,ZmFrZQ==',
                'emv' => '00020126580014br.gov.bcb.pix123',
            ]);

        $boletoService = new BoletoService($apiClient);

        $service = new PaytimeClient($apiClient, $boletoService);
        $response = $service->createPixPayment($order);

        $this->assertSame('pix-api-123', $response['gateway_transaction_id']);
        $this->assertSame('PENDING', $response['gateway_status']);
        $this->assertSame('pending', $response['internal_status']);
        $this->assertSame('00020126580014br.gov.bcb.pix123', $response['pix_qr_code']);
        $this->assertSame('00020126580014br.gov.bcb.pix123', $response['pix_copy_paste']);
        $this->assertSame('data:image/png;base64,ZmFrZQ==', $response['pix_qr_code_image']);
        $this->assertSame('2026-05-04T12:30:00Z', $response['pix_expires_at']);
        $this->assertSame('pix-api-123', $response['api_transaction']['_id']);
        $this->assertSame('data:image/png;base64,ZmFrZQ==', $response['api_qrcode']['qrcode']);

        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context) use ($order): bool {
            return $message === 'Paytime Pix transaction response received'
                && ($context['order_number'] ?? null) === $order->order_number
                && ($context['transaction_id'] ?? null) === 'pix-api-123'
                && in_array('_id', $context['transaction_keys'] ?? [], true);
        });
        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context): bool {
            return $message === 'Paytime Pix qrcode response received'
                && ($context['transaction_id'] ?? null) === 'pix-api-123'
                && in_array('qrcode', $context['qrcode_keys'] ?? [], true);
        });
    }

    public function test_create_pix_payment_logs_when_transaction_id_is_missing(): void
    {
        Log::spy();

        $seller = $this->makeVendorUser('127700');
        $product = $this->makeProduct($seller);
        $checkoutLink = $this->makeCheckoutLink($seller, $product);
        $checkoutSession = $this->makeCheckoutSession($checkoutLink);
        $order = $this->makeOrder($seller, $checkoutLink, $checkoutSession, $product);

        $apiClient = $this->createMock(ApiClientService::class);
        $apiClient->expects($this->once())
            ->method('post')
            ->willReturn([
                'status' => 'PENDING',
                'expected_on' => '2026-05-04T12:30:00Z',
            ]);

        $apiClient->expects($this->never())
            ->method('get');

        $boletoService = $this->createMock(BoletoService::class);

        $service = new PaytimeClient($apiClient, $boletoService);
        $response = $service->createPixPayment($order);

        $this->assertSame('PENDING', $response['status']);
        $this->assertArrayNotHasKey('gateway_transaction_id', $response);

        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context): bool {
            return $message === 'Paytime Pix transaction response did not include a transaction id'
                && ($context['order_number'] ?? null) === 'JNT-2026-000001'
                && isset($context['transaction_payload']['status'])
                && $context['transaction_payload']['status'] === 'PENDING';
        });
    }

    public function test_create_boleto_payment_uses_the_gateway_and_normalizes_the_response(): void
    {
        Log::spy();

        $seller = $this->makeVendorUser('127700');
        $product = $this->makeProduct($seller);
        $checkoutLink = $this->makeCheckoutLink($seller, $product, [
            'name' => 'Link Boleto Público',
            'expires_at' => Carbon::parse('2026-05-12 10:00:00'),
        ]);
        $checkoutSession = $this->makeCheckoutSession($checkoutLink, [
            'street' => 'Rua A',
            'number' => '100',
            'complement' => 'Apto 1',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zipcode' => '01001000',
            'last_activity_at' => Carbon::parse('2026-05-04 10:00:00'),
        ]);
        $order = $this->makeOrder($seller, $checkoutLink, $checkoutSession, $product, [
            'payment_method' => 'boleto',
        ]);

        $apiClient = $this->createMock(ApiClientService::class);
        $apiClient->expects($this->once())
            ->method('post')
            ->with(
                'marketplace/billets',
                $this->callback(function (array $payload): bool {
                    return $payload['amount'] === 15000
                        && $payload['expiration'] === '2026-05-12'
                        && $payload['payment_limit_date'] === '2026-05-13';
                }),
            )
            ->willReturn([
                '_id' => 'boleto-api-123',
                'status' => 'PROCESSING',
                'url' => null,
                'barcode' => null,
                'digitable_line' => null,
            ]);
        $apiClient->expects($this->once())
            ->method('get')
            ->with('marketplace/billets/boleto-api-123')
            ->willReturn([
                '_id' => 'boleto-api-123',
                'status' => 'PROCESSING',
                'url' => 'https://example.test/boleto.pdf',
                'barcode' => '12345678901234567890123456789012345678901234',
                'digitable_line' => '23793.38128 60000.000000 01000.000000 1 98760000002000',
            ]);

        $boletoService = new BoletoService($apiClient);
        $service = new PaytimeClient($apiClient, $boletoService);
        $response = $service->createBoletoPayment($order);

        $this->assertSame('boleto-api-123', $response['gateway_transaction_id']);
        $this->assertSame('PROCESSING', $response['gateway_status']);
        $this->assertSame('pending', $response['internal_status']);
        $this->assertSame('https://example.test/boleto.pdf', $response['boleto_url']);
        $this->assertSame('12345678901234567890123456789012345678901234', $response['boleto_barcode']);
        $this->assertSame('23793.38128 60000.000000 01000.000000 1 98760000002000', $response['boleto_digitable_line']);
        $this->assertSame('boleto-api-123', $response['api_boleto']['_id']);

        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context): bool {
            return $message === 'Paytime Boleto response received'
                && ($context['transaction_id'] ?? null) === 'boleto-api-123';
        });
    }

    public function test_create_credit_card_payment_uses_the_gateway_and_normalizes_the_response(): void
    {
        Log::spy();

        $seller = $this->makeVendorUser('127700');
        $product = $this->makeProduct($seller);
        $checkoutLink = $this->makeCheckoutLink($seller, $product);
        $checkoutSession = $this->makeCheckoutSession($checkoutLink, [
            'street' => 'Rua A',
            'number' => '100',
            'complement' => 'Apto 1',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zipcode' => '01001000',
        ]);
        $order = $this->makeOrder($seller, $checkoutLink, $checkoutSession, $product, [
            'payment_method' => 'credit_card',
        ]);

        $apiClient = $this->createMock(ApiClientService::class);
        $apiClient->expects($this->once())
            ->method('post')
            ->with(
                'marketplace/transactions',
                $this->callback(function (array $payload) use ($order): bool {
                    return $payload['payment_type'] === 'CREDIT'
                        && $payload['amount'] === 15000
                        && $payload['installments'] === 3
                        && $payload['interest'] === 'CLIENT'
                        && $payload['client']['first_name'] === 'Maria'
                        && $payload['client']['last_name'] === 'Silva'
                        && $payload['client']['email'] === 'maria@example.com'
                        && $payload['client']['phone'] === '11999999999'
                        && ! isset($payload['customer'])
                        && $payload['client']['document'] === '12345678909'
                        && $payload['client']['address']['street'] === 'Rua A'
                        && $payload['client']['address']['number'] === '100'
                        && $payload['client']['address']['complement'] === 'Apto 1'
                        && $payload['client']['address']['neighborhood'] === 'Centro'
                        && $payload['client']['address']['city'] === 'São Paulo'
                        && $payload['client']['address']['state'] === 'SP'
                        && $payload['client']['address']['zip_code'] === '01001000'
                        && $payload['card']['holder_name'] === 'Maria Silva'
                        && $payload['card']['holder_document'] === '12345678909'
                        && $payload['card']['card_number'] === '4111111111111111'
                        && $payload['card']['expiration_month'] === 12
                        && $payload['card']['expiration_year'] === 2027
                        && $payload['card']['security_code'] === '123'
                        && $payload['extra_headers']['establishment_id'] === '127700'
                        && $payload['session_id'] === 'checkout_'.$order->checkout_session_id
                        && $payload['info_additional'][0]['value'] === $order->order_number
                        && $payload['info_additional'][1]['value'] === (string) $order->checkout_link_id
                        && $payload['info_additional'][2]['value'] === (string) $order->checkout_session_id;
                }),
            )
            ->willReturn([
                '_id' => 'card-api-123',
                'status' => 'AUTHORIZED',
                'card' => [
                    'brand_name' => 'MASTERCARD',
                    'last4_digits' => '1111',
                ],
            ]);

        $boletoService = new BoletoService($apiClient);
        $service = new PaytimeClient($apiClient, $boletoService);
        $response = $service->createCreditCardPayment($order, [
            'payment_method' => 'credit_card',
            'installments' => 3,
            'card' => [
                'holder_name' => 'Maria Silva',
                'holder_document' => '123.456.789-09',
                'card_number' => '4111 1111 1111 1111',
                'expiration_month' => 12,
                'expiration_year' => 2027,
                'security_code' => '123',
            ],
        ]);

        $this->assertSame('card-api-123', $response['gateway_transaction_id']);
        $this->assertSame('AUTHORIZED', $response['gateway_status']);
        $this->assertSame('authorized', $response['internal_status']);
        $this->assertSame('1111', $response['card_last_four']);
        $this->assertSame('MASTERCARD', $response['card_brand']);
        $this->assertSame(3, $response['installments']);
        $this->assertSame('card-api-123', $response['api_transaction']['_id']);

        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context) use ($order): bool {
            return $message === 'Paytime credit card transaction response received'
                && ($context['order_number'] ?? null) === $order->order_number
                && ($context['transaction_id'] ?? null) === 'card-api-123'
                && in_array('_id', $context['transaction_keys'] ?? [], true);
        });
    }

    public function test_create_credit_card_payment_normalizes_phone_digits_without_nested_customer_phones(): void
    {
        Log::spy();

        $seller = $this->makeVendorUser('127700');
        $product = $this->makeProduct($seller);
        $checkoutLink = $this->makeCheckoutLink($seller, $product);
        $checkoutSession = $this->makeCheckoutSession($checkoutLink, [
            'customer_phone' => '11 2600-3909',
            'street' => 'Rua A',
            'number' => '100',
            'complement' => 'Apto 1',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zipcode' => '01001000',
        ]);
        $order = $this->makeOrder($seller, $checkoutLink, $checkoutSession, $product, [
            'payment_method' => 'credit_card',
            'customer_phone' => '11 2600-3909',
        ]);

        $apiClient = $this->createMock(ApiClientService::class);
        $apiClient->expects($this->once())
            ->method('post')
            ->with(
                'marketplace/transactions',
                $this->callback(function (array $payload): bool {
                    return $payload['client']['phone'] === '1126003909'
                        && ! isset($payload['customer'])
                        && $payload['client']['first_name'] === 'Maria'
                        && $payload['client']['document'] === '12345678909';
                }),
            )
            ->willReturn([
                '_id' => 'card-api-phone-123',
                'status' => 'AUTHORIZED',
                'card' => [
                    'brand_name' => 'VISA',
                    'last4_digits' => '1111',
                ],
            ]);

        $boletoService = new BoletoService($apiClient);
        $service = new PaytimeClient($apiClient, $boletoService);
        $response = $service->createCreditCardPayment($order, [
            'payment_method' => 'credit_card',
            'installments' => 3,
            'card' => [
                'holder_name' => 'Maria Silva',
                'holder_document' => '123.456.789-09',
                'card_number' => '4111 1111 1111 1111',
                'expiration_month' => 12,
                'expiration_year' => 2027,
                'security_code' => '123',
            ],
        ]);

        $this->assertSame('card-api-phone-123', $response['gateway_transaction_id']);
        $this->assertSame('AUTHORIZED', $response['gateway_status']);
        $this->assertSame('authorized', $response['internal_status']);
    }

    public function test_create_credit_card_payment_resolves_a_nested_transaction_id_when_the_gateway_wraps_the_response(): void
    {
        Log::spy();

        $seller = $this->makeVendorUser('127700');
        $product = $this->makeProduct($seller);
        $checkoutLink = $this->makeCheckoutLink($seller, $product);
        $checkoutSession = $this->makeCheckoutSession($checkoutLink, [
            'street' => 'Rua A',
            'number' => '100',
            'complement' => 'Apto 1',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zipcode' => '01001000',
        ]);
        $order = $this->makeOrder($seller, $checkoutLink, $checkoutSession, $product, [
            'payment_method' => 'credit_card',
        ]);

        $apiClient = $this->createMock(ApiClientService::class);
        $apiClient->expects($this->once())
            ->method('post')
            ->willReturn([
                'status' => 'AUTHORIZED',
                'card' => [
                    'brand_name' => 'VISA',
                    'last4_digits' => '1111',
                ],
                'data' => [
                    'transaction' => [
                        '_id' => 'card-api-nested-123',
                    ],
                ],
            ]);

        $boletoService = new BoletoService($apiClient);
        $service = new PaytimeClient($apiClient, $boletoService);
        $response = $service->createCreditCardPayment($order, [
            'payment_method' => 'credit_card',
            'installments' => 3,
            'card' => [
                'holder_name' => 'Maria Silva',
                'holder_document' => '123.456.789-09',
                'card_number' => '4111 1111 1111 1111',
                'expiration_month' => 12,
                'expiration_year' => 2027,
                'security_code' => '123',
            ],
        ]);

        $this->assertSame('card-api-nested-123', $response['gateway_transaction_id']);
        $this->assertSame('card-api-nested-123', $response['transaction_id']);
        $this->assertSame('AUTHORIZED', $response['gateway_status']);
        $this->assertSame('authorized', $response['internal_status']);
        $this->assertSame('1111', $response['card_last_four']);
        $this->assertSame('VISA', $response['card_brand']);
    }

    public function test_create_credit_card_payment_preserves_zero_padded_expiration_month(): void
    {
        Log::spy();

        $seller = $this->makeVendorUser('127700');
        $product = $this->makeProduct($seller);
        $checkoutLink = $this->makeCheckoutLink($seller, $product);
        $checkoutSession = $this->makeCheckoutSession($checkoutLink, [
            'street' => 'Rua A',
            'number' => '100',
            'complement' => 'Apto 1',
            'neighborhood' => 'Centro',
            'city' => 'SÃ£o Paulo',
            'state' => 'SP',
            'zipcode' => '01001000',
        ]);
        $order = $this->makeOrder($seller, $checkoutLink, $checkoutSession, $product, [
            'payment_method' => 'credit_card',
        ]);

        $sentPayload = [];
        $apiClient = $this->createMock(ApiClientService::class);
        $apiClient->expects($this->once())
            ->method('post')
            ->with(
                'marketplace/transactions',
                $this->callback(function (array $payload) use (&$sentPayload): bool {
                    $sentPayload = $payload;

                    return $payload['card']['expiration_month'] === 7;
                }),
            )
            ->willReturn([
                '_id' => 'card-api-124',
                'status' => 'AUTHORIZED',
                'card' => [
                    'brand_name' => 'MASTERCARD',
                    'last4_digits' => '1111',
                ],
            ]);

        $boletoService = new BoletoService($apiClient);
        $service = new PaytimeClient($apiClient, $boletoService);
        $response = $service->createCreditCardPayment($order, [
            'payment_method' => 'credit_card',
            'installments' => 1,
            'card' => [
                'holder_name' => 'Maria Silva',
                'holder_document' => '123.456.789-09',
                'card_number' => '4111 1111 1111 1111',
                'expiration_month' => 7,
                'expiration_year' => 2027,
                'security_code' => '123',
            ],
        ]);

        $this->assertSame('card-api-124', $response['gateway_transaction_id']);
        $this->assertSame(7, $sentPayload['card']['expiration_month']);
    }

    public function test_create_credit_card_payment_flags_3ds_when_gateway_requires_authentication(): void
    {
        Log::spy();

        $seller = $this->makeVendorUser('127700');
        $product = $this->makeProduct($seller);
        $checkoutLink = $this->makeCheckoutLink($seller, $product);
        $checkoutSession = $this->makeCheckoutSession($checkoutLink, [
            'street' => 'Rua A',
            'number' => '100',
            'complement' => 'Apto 1',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zipcode' => '01001000',
        ]);
        $order = $this->makeOrder($seller, $checkoutLink, $checkoutSession, $product, [
            'payment_method' => 'credit_card',
        ]);

        $apiClient = $this->createMock(ApiClientService::class);
        $apiClient->expects($this->once())
            ->method('post')
            ->with(
                'marketplace/transactions',
                $this->callback(function (array $payload): bool {
                    return $payload['payment_type'] === 'CREDIT'
                        && $payload['amount'] === 15000
                        && $payload['installments'] === 3
                        && $payload['card']['card_number'] === '4111111111111111';
                }),
            )
            ->willReturn([
                '_id' => 'card-api-3ds-123',
                'status' => 'PENDING',
                'antifraud' => [
                    [
                        'analyse_required' => 'THREEDS',
                        'analyse_status' => 'WAITING_AUTH',
                        'session' => '3DS_SESSION_123',
                    ],
                ],
            ]);

        $boletoService = new BoletoService($apiClient);
        $service = new PaytimeClient($apiClient, $boletoService);
        $response = $service->createCreditCardPayment($order, [
            'payment_method' => 'credit_card',
            'installments' => 3,
            'card' => [
                'holder_name' => 'Maria Silva',
                'holder_document' => '123.456.789-09',
                'card_number' => '4111 1111 1111 1111',
                'expiration_month' => 12,
                'expiration_year' => 2027,
                'security_code' => '123',
            ],
        ]);

        $this->assertTrue($response['requires_3ds']);
        $this->assertSame('pending', $response['internal_status']);
        $this->assertSame('3DS_SESSION_123', $response['session_id']);
        $this->assertSame('card-api-3ds-123', $response['transaction_id']);
        $this->assertSame('Transação criada, aguardando autenticação 3DS.', $response['message']);
    }

    public function test_confirm_credit_card_3ds_uses_the_gateway_and_normalizes_the_response(): void
    {
        $apiClient = $this->createMock(ApiClientService::class);
        $apiClient->expects($this->once())
            ->method('post')
            ->with(
                'marketplace/transactions/card-api-123/antifraud-auth',
                $this->callback(function (array $payload): bool {
                    return $payload['id'] === '3DS_123'
                        && $payload['status'] === 'AUTH_FLOW_COMPLETED'
                        && $payload['authentication_status'] === 'AUTHENTICATED';
                }),
            )
            ->willReturn([
                '_id' => 'card-api-123',
                'status' => 'AUTHORIZED',
            ]);

        $boletoService = new BoletoService($apiClient);
        $service = new PaytimeClient($apiClient, $boletoService);
        $response = $service->confirmCreditCard3ds('card-api-123', [
            'id' => '3DS_123',
            'status' => 'AUTH_FLOW_COMPLETED',
            'authentication_status' => 'AUTHENTICATED',
        ]);

        $this->assertSame('card-api-123', $response['gateway_transaction_id']);
        $this->assertSame('AUTHORIZED', $response['gateway_status']);
        $this->assertSame('authorized', $response['internal_status']);
        $this->assertSame('card-api-123', $response['api_transaction']['_id']);
    }

    private function makeVendorUser(string $establishmentId): User
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => $establishmentId,
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]);

        return $user;
    }

    private function makeProduct(User $seller): Product
    {
        return Product::query()->create([
            'seller_id' => $seller->id,
            'name' => 'Produto de checkout',
            'slug' => 'produto-de-checkout',
            'description' => 'Descrição do produto',
            'short_description' => 'Resumo',
            'sku' => 'SKU-001',
            'price' => 150.00,
            'status' => 'active',
        ]);
    }

    private function makeCheckoutLink(User $seller, Product $product, array $overrides = []): CheckoutLink
    {
        return CheckoutLink::query()->create(array_merge([
            'seller_id' => $seller->id,
            'product_id' => $product->id,
            'public_token' => CheckoutLink::generatePublicToken(),
            'name' => 'Link Pix',
            'status' => 'active',
            'quantity' => 1,
            'unit_price' => 150.00,
            'total_price' => 150.00,
            'allow_pix' => true,
            'allow_boleto' => true,
            'allow_credit_card' => true,
            'pix_discount_type' => 'none',
            'pix_discount_value' => 0,
            'boleto_discount_type' => 'none',
            'boleto_discount_value' => 0,
            'free_shipping' => true,
        ], $overrides));
    }

    private function makeCheckoutSession(CheckoutLink $checkoutLink, array $overrides = []): CheckoutSession
    {
        return CheckoutSession::query()->create(array_merge([
            'checkout_link_id' => $checkoutLink->id,
            'seller_id' => $checkoutLink->seller_id,
            'product_id' => $checkoutLink->product_id,
            'session_token' => CheckoutSession::generateSessionToken(),
            'status' => 'delivery_completed',
            'current_step' => 'payment',
            'subtotal' => 150.00,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => 150.00,
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
            'recipient_name' => 'Maria Silva',
            'last_activity_at' => now(),
        ], $overrides));
    }

    private function makeOrder(User $seller, CheckoutLink $checkoutLink, CheckoutSession $checkoutSession, Product $product, array $overrides = []): Order
    {
        return Order::query()->create(array_merge([
            'seller_id' => $seller->id,
            'checkout_link_id' => $checkoutLink->id,
            'checkout_session_id' => $checkoutSession->id,
            'product_id' => $product->id,
            'order_number' => 'JNT-2026-000001',
            'status' => 'pending',
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_phone' => '11999999999',
            'quantity' => 1,
            'unit_price' => 150.00,
            'subtotal' => 150.00,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => 150.00,
            'payment_method' => 'pix',
        ], $overrides));
    }
}
