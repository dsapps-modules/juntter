<?php

namespace Tests\Feature;

use App\Models\CheckoutEvent;
use App\Models\CheckoutLink;
use App\Models\CheckoutSession;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectedCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_can_create_a_product(): void
    {
        $user = $this->makeVendorUser();

        $response = $this->actingAs($user)->postJson('/seller/products', [
            'name' => 'Curso Laravel',
            'slug' => 'curso-laravel',
            'description' => 'Descrição do curso',
            'short_description' => 'Curso prático',
            'sku' => 'SKU-001',
            'price' => 199.90,
            'status' => 'active',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('products', [
            'seller_id' => $user->id,
            'name' => 'Curso Laravel',
            'slug' => 'curso-laravel',
            'price' => 199.90,
            'status' => 'active',
        ]);
    }

    public function test_vendor_can_create_a_checkout_link(): void
    {
        $user = $this->makeVendorUser();
        $product = $this->makeProduct($user);

        $response = $this->actingAs($user)->postJson('/seller/checkout-links', [
            'product_id' => $product->id,
            'name' => 'Oferta principal',
            'status' => 'active',
            'quantity' => 2,
            'unit_price' => 149.90,
            'allow_pix' => true,
            'allow_boleto' => true,
            'allow_credit_card' => true,
            'pix_discount_type' => 'percentage',
            'pix_discount_value' => 10,
            'boleto_discount_type' => 'fixed',
            'boleto_discount_value' => 20,
            'free_shipping' => true,
        ]);

        $response->assertCreated();
        $this->assertStringStartsWith('chk_', (string) $response->json('checkout_link.public_token'));
        $this->assertSame(route('checkout.public.show', $response->json('checkout_link.public_token')), $response->json('public_url'));

        $this->assertDatabaseHas('checkout_links', [
            'seller_id' => $user->id,
            'product_id' => $product->id,
            'name' => 'Oferta principal',
            'status' => 'active',
            'quantity' => 2,
            'unit_price' => 149.90,
        ]);
    }

    public function test_vendor_can_update_a_product(): void
    {
        $user = $this->makeVendorUser();
        $product = $this->makeProduct($user);

        $response = $this->actingAs($user)->putJson('/seller/products/'.$product->id, [
            'name' => 'Curso Laravel Avançado',
            'slug' => 'curso-laravel-avancado',
            'description' => 'Nova descrição',
            'short_description' => 'Resumo novo',
            'sku' => 'SKU-002',
            'price' => 249.90,
            'status' => 'active',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Curso Laravel Avançado',
            'slug' => 'curso-laravel-avancado',
            'price' => 249.90,
        ]);
    }

    public function test_vendor_can_activate_and_deactivate_a_checkout_link(): void
    {
        $user = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($user, $this->makeProduct($user), ['status' => 'inactive']);

        $this->actingAs($user)
            ->postJson('/seller/checkout-links/'.$link->id.'/activate')
            ->assertOk();

        $this->assertDatabaseHas('checkout_links', [
            'id' => $link->id,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->postJson('/seller/checkout-links/'.$link->id.'/deactivate')
            ->assertOk();

        $this->assertDatabaseHas('checkout_links', [
            'id' => $link->id,
            'status' => 'inactive',
        ]);
    }

    public function test_vendor_can_delete_a_checkout_link(): void
    {
        $user = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($user, $this->makeProduct($user));

        $this->actingAs($user)
            ->deleteJson('/seller/checkout-links/'.$link->id)
            ->assertOk();

        $this->assertDatabaseMissing('checkout_links', [
            'id' => $link->id,
        ]);
    }

    public function test_vendor_can_delete_a_product(): void
    {
        $user = $this->makeVendorUser();
        $product = $this->makeProduct($user);

        $this->actingAs($user)
            ->deleteJson('/seller/products/'.$product->id)
            ->assertOk();

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    public function test_vendor_cannot_access_another_vendors_checkout_link(): void
    {
        $sellerOne = $this->makeVendorUser();
        $sellerTwo = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($sellerOne, $this->makeProduct($sellerOne));

        $this->actingAs($sellerTwo)
            ->get('/seller/checkout-links/'.$link->id)
            ->assertForbidden();
    }

    public function test_active_public_checkout_opens_checkout_page(): void
    {
        $user = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($user, $this->makeProduct($user));

        $response = $this->get(route('checkout.public.show', $link->public_token));

        $response->assertOk();
        $response->assertSee($link->product->name);
    }

    public function test_inactive_public_checkout_returns_unavailable_page(): void
    {
        $user = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($user, $this->makeProduct($user), ['status' => 'inactive']);

        $response = $this->get(route('checkout.public.show', $link->public_token));

        $response->assertStatus(410);
        $response->assertSee('Este checkout não está disponível no momento.');
    }

    public function test_frontend_price_is_ignored_when_starting_pix_payment(): void
    {
        $user = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($user, $this->makeProduct($user), [
            'quantity' => 1,
            'unit_price' => 150.00,
            'total_price' => 150.00,
            'allow_pix' => true,
            'allow_boleto' => false,
            'allow_credit_card' => false,
        ]);
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);

        $response = $this->postJson('/checkout/session/'.$session->session_token.'/payment', [
            'payment_method' => 'pix',
            'total' => 1.00,
            'unit_price' => 1.00,
            'installments' => 1,
        ]);

        $response->assertOk();
        $this->assertSame('150.00', (string) $response->json('order.total'));
        $this->assertSame('150.00', (string) $response->json('payment_transaction.amount'));
    }

    public function test_identification_is_saved(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
        $session = $this->makeCheckoutSession($link);

        $response = $this->postJson('/checkout/session/'.$session->session_token.'/identification', [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('checkout_sessions', [
            'id' => $session->id,
            'customer_name' => 'Maria Silva',
            'status' => 'identification_completed',
            'current_step' => 'delivery',
        ]);
    }

    public function test_delivery_is_saved(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
        $session = $this->makeCheckoutSession($link);

        $response = $this->postJson('/checkout/session/'.$session->session_token.'/delivery', [
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'complement' => 'Apto 1',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('checkout_sessions', [
            'id' => $session->id,
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);
    }

    public function test_pix_payment_creates_order_and_transaction(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);

        $response = $this->postJson('/checkout/session/'.$session->session_token.'/payment', [
            'payment_method' => 'pix',
            'total' => 1.00,
            'unit_price' => 1.00,
            'installments' => 1,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('orders', [
            'checkout_session_id' => $session->id,
            'payment_method' => 'pix',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('payment_transactions', [
            'payment_method' => 'pix',
            'gateway' => 'paytime',
            'internal_status' => 'pending',
        ]);
    }

    public function test_paytime_webhook_approves_order(): void
    {
        config()->set('services.paytime.webhook_user', 'webhook-user');
        config()->set('services.paytime.webhook_pass', 'webhook-pass');

        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);

        $paymentResponse = $this->postJson('/checkout/session/'.$session->session_token.'/payment', [
            'payment_method' => 'pix',
            'installments' => 1,
        ]);

        $orderNumber = $paymentResponse->json('order.order_number');
        $transactionId = $paymentResponse->json('payment_transaction.gateway_transaction_id');

        $response = $this
            ->withBasicAuth('webhook-user', 'webhook-pass')
            ->postJson('/api/webhook/paytime', [
                'event' => 'payment-approved',
                'order_number' => $orderNumber,
                'gateway_transaction_id' => $transactionId,
                'status' => 'PAID',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('orders', [
            'order_number' => $orderNumber,
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('payment_transactions', [
            'gateway_transaction_id' => $transactionId,
            'internal_status' => 'paid',
        ]);

        $this->assertDatabaseHas('checkout_sessions', [
            'id' => $session->id,
            'status' => 'paid',
            'current_step' => 'confirmation',
        ]);
    }

    public function test_duplicate_webhook_does_not_duplicate_approval_event(): void
    {
        config()->set('services.paytime.webhook_user', 'webhook-user');
        config()->set('services.paytime.webhook_pass', 'webhook-pass');

        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'status' => 'delivery_completed',
            'current_step' => 'payment',
        ]);

        $paymentResponse = $this->postJson('/checkout/session/'.$session->session_token.'/payment', [
            'payment_method' => 'pix',
            'installments' => 1,
        ]);

        $orderNumber = $paymentResponse->json('order.order_number');
        $transactionId = $paymentResponse->json('payment_transaction.gateway_transaction_id');

        $payload = [
            'event' => 'payment-approved',
            'order_number' => $orderNumber,
            'gateway_transaction_id' => $transactionId,
            'status' => 'PAID',
        ];

        $this->withBasicAuth('webhook-user', 'webhook-pass')->postJson('/api/webhook/paytime', $payload)->assertOk();
        $this->withBasicAuth('webhook-user', 'webhook-pass')->postJson('/api/webhook/paytime', $payload)->assertOk();

        $this->assertSame(1, CheckoutEvent::query()->where('event_type', 'payment_approved')->count());
        $this->assertDatabaseHas('orders', [
            'order_number' => $orderNumber,
            'status' => 'paid',
        ]);
    }

    public function test_checkout_is_marked_as_abandoned(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
        $session = $this->makeCheckoutSession($link, [
            'status' => 'payment_started',
            'current_step' => 'payment',
            'last_activity_at' => now()->subMinutes(31),
        ]);

        $this->artisan('checkout:mark-abandoned')->assertExitCode(0);

        $this->assertDatabaseHas('checkout_sessions', [
            'id' => $session->id,
            'status' => 'abandoned',
        ]);
    }

    public function test_recovery_is_not_sent_for_paid_order(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
        $session = $this->makeCheckoutSession($link, [
            'customer_email' => 'maria@example.com',
            'status' => 'abandoned',
            'current_step' => 'payment',
            'last_activity_at' => now()->subMinutes(31),
        ]);

        Order::query()->create([
            'seller_id' => $link->seller_id,
            'checkout_link_id' => $link->id,
            'checkout_session_id' => $session->id,
            'product_id' => $link->product_id,
            'order_number' => 'JNT-2026-000001',
            'status' => 'paid',
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_phone' => '11999999999',
            'quantity' => 1,
            'unit_price' => 100.00,
            'subtotal' => 100.00,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => 100.00,
            'payment_method' => 'pix',
        ]);

        $this->artisan('checkout:send-recovery-messages')->assertExitCode(0);

        $this->assertDatabaseHas('abandoned_checkout_recoveries', [
            'checkout_session_id' => $session->id,
            'status' => 'skipped',
        ]);

        $this->assertDatabaseMissing('abandoned_checkout_recoveries', [
            'checkout_session_id' => $session->id,
            'status' => 'sent',
        ]);
    }

    private function makeVendorUser(): User
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => (string) random_int(100000, 999999),
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]);

        return $user;
    }

    private function makeProduct(User $seller, array $overrides = []): Product
    {
        return Product::query()->create(array_merge([
            'seller_id' => $seller->id,
            'name' => 'Produto '.random_int(1, 9999),
            'slug' => 'produto-'.random_int(1, 9999),
            'description' => 'Descrição do produto',
            'short_description' => 'Resumo',
            'sku' => 'SKU-'.random_int(1, 9999),
            'price' => 100.00,
            'status' => 'active',
        ], $overrides));
    }

    private function makeCheckoutLink(User $seller, Product $product, array $overrides = []): CheckoutLink
    {
        $quantity = $overrides['quantity'] ?? 1;
        $unitPrice = $overrides['unit_price'] ?? 100.00;

        return CheckoutLink::query()->create(array_merge([
            'seller_id' => $seller->id,
            'product_id' => $product->id,
            'public_token' => CheckoutLink::generatePublicToken(),
            'name' => 'Link '.random_int(1, 9999),
            'status' => 'active',
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => round($quantity * $unitPrice, 2),
            'allow_pix' => true,
            'allow_boleto' => true,
            'allow_credit_card' => true,
            'pix_discount_type' => 'none',
            'pix_discount_value' => 0,
            'boleto_discount_type' => 'none',
            'boleto_discount_value' => 0,
            'free_shipping' => true,
            'visual_config' => [
                'store_name' => 'Loja Teste',
                'primary_color' => '#111827',
            ],
        ], $overrides));
    }

    private function makeCheckoutSession(CheckoutLink $link, array $overrides = []): CheckoutSession
    {
        return CheckoutSession::query()->create(array_merge([
            'checkout_link_id' => $link->id,
            'seller_id' => $link->seller_id,
            'product_id' => $link->product_id,
            'session_token' => CheckoutSession::generateSessionToken(),
            'status' => 'started',
            'current_step' => 'identification',
            'subtotal' => $link->total_price,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => $link->total_price,
            'last_activity_at' => now(),
        ], $overrides));
    }
}
