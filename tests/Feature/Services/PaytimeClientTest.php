<?php

namespace Tests\Feature\Services;

use App\Models\CheckoutLink;
use App\Models\CheckoutSession;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\ApiClientService;
use App\Services\Payments\Paytime\PaytimeClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaytimeClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_pix_payment_uses_the_api_and_normalizes_the_response(): void
    {
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

        $service = new PaytimeClient($apiClient);
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

    private function makeCheckoutLink(User $seller, Product $product): CheckoutLink
    {
        return CheckoutLink::query()->create([
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
        ]);
    }

    private function makeCheckoutSession(CheckoutLink $checkoutLink): CheckoutSession
    {
        return CheckoutSession::query()->create([
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
        ]);
    }

    private function makeOrder(User $seller, CheckoutLink $checkoutLink, CheckoutSession $checkoutSession, Product $product): Order
    {
        return Order::query()->create([
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
        ]);
    }
}
