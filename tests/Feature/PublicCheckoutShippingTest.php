<?php

namespace Tests\Feature;

use App\Models\CheckoutLink;
use App\Models\CheckoutSession;
use App\Models\CheckoutShippingOption;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCheckoutShippingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_checkout_spa_delivery_tab_renders_the_shipping_selector(): void
    {
        $seller = $this->makeVendorUser();
        $product = $this->makeProduct($seller);
        $this->makeShippingOption($seller, [
            'name' => 'Frete padrão',
            'price' => 0,
            'eta_days' => 5,
            'is_default' => true,
        ]);
        $link = $this->makeCheckoutLink($seller, $product);
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document_type' => 'cpf',
            'customer_document' => '12345678909',
            'customer_phone' => '11999999999',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'current_step' => 'delivery',
        ]);

        $response = $this->get(route('checkout.public.spa.show', $link->public_token));
        $source = file_get_contents(resource_path('js/checkout-spa.jsx'));

        $response->assertOk();
        $response->assertSee('shippingOptions', false);
        $response->assertSee('checkout-spa-data', false);
        $this->assertNotFalse($source);
        $this->assertStringContainsString('function renderShippingSelector()', $source);
        $this->assertStringContainsString('function renderResidentialAddressForm()', $source);
        $this->assertStringContainsString('function renderDeliveryAddressFields()', $source);
        $this->assertStringContainsString('function renderDeliverySummaryAndShipping()', $source);
        $this->assertStringContainsString('checkout-spa-shipping-grid', $source);
        $this->assertStringContainsString('checkout-spa-delivery-summary', $source);
        $this->assertStringContainsString('checkout-spa-link-button', $source);
        $this->assertStringContainsString('+ Alterar endereço de entrega', $source);
        $this->assertStringContainsString('Usar endereço cadastrado', $source);
        $this->assertStringContainsString('Endereço do comprador', $source);
        $this->assertStringContainsString('Endereço de entrega', $source);
        $this->assertStringContainsString('checkout-spa-essential-delivery-section', $source);
    }

    public function test_saving_delivery_persists_the_selected_shipping_option_and_keeps_billing_address_separate(): void
    {
        $seller = $this->makeVendorUser();
        $product = $this->makeProduct($seller);
        $expressOption = $this->makeShippingOption($seller, [
            'name' => 'Frete expresso',
            'price' => 19.90,
            'eta_days' => 2,
            'is_default' => false,
        ]);
        $link = $this->makeCheckoutLink($seller, $product);
        $session = $this->makeCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document_type' => 'cpf',
            'customer_document' => '12345678909',
            'customer_phone' => '11999999999',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'complement' => 'Apto 1',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'current_step' => 'delivery',
            'subtotal' => 100.00,
            'total' => 100.00,
        ]);

        $response = $this->postJson(route('checkout.public.delivery', $session->session_token), [
            'delivery_zipcode' => '01310100',
            'delivery_street' => 'Avenida Paulista',
            'delivery_number' => '1500',
            'delivery_complement' => 'Conjunto 12',
            'delivery_neighborhood' => 'Bela Vista',
            'delivery_city' => 'São Paulo',
            'delivery_state' => 'SP',
            'delivery_recipient_name' => 'Maria Silva',
            'shipping_option_id' => $expressOption->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('checkout_session.shipping_option_id', $expressOption->id);
        $response->assertJsonPath('checkout_session.shipping_option_name', 'Frete expresso');
        $response->assertJsonPath('checkout_session.zipcode', '01001000');
        $response->assertJsonPath('checkout_session.delivery_zipcode', '01310100');

        $freshSession = $session->fresh();
        $this->assertSame('01001000', $freshSession->zipcode);
        $this->assertSame('Rua A', $freshSession->street);
        $this->assertSame('01310100', $freshSession->delivery_zipcode);
        $this->assertSame('Avenida Paulista', $freshSession->delivery_street);
        $this->assertSame((float) $expressOption->price, (float) $freshSession->shipping_total);
        $this->assertSame('Frete expresso', $freshSession->shipping_option_name);
        $this->assertSame(119.9, (float) $freshSession->total);

        $this->assertDatabaseHas('checkout_events', [
            'checkout_session_id' => $session->id,
            'event_type' => 'delivery_completed',
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

    private function makeProduct(User $seller): Product
    {
        return Product::query()->create([
            'seller_id' => $seller->id,
            'name' => 'Produto '.random_int(1, 9999),
            'slug' => 'produto-'.random_int(1, 9999),
            'description' => 'Descrição do produto',
            'short_description' => 'Resumo',
            'sku' => 'SKU-'.random_int(1, 9999),
            'price' => 100.00,
            'status' => 'active',
        ]);
    }

    private function makeCheckoutLink(User $seller, Product $product, array $overrides = []): CheckoutLink
    {
        return CheckoutLink::query()->create(array_merge([
            'seller_id' => $seller->id,
            'product_id' => $product->id,
            'public_token' => CheckoutLink::generatePublicToken(),
            'name' => 'Link '.random_int(1, 9999),
            'status' => 'active',
            'quantity' => 1,
            'unit_price' => 100.00,
            'total_price' => 100.00,
            'allow_pix' => true,
            'allow_boleto' => true,
            'allow_credit_card' => true,
            'request_address' => true,
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
            'quantity' => 1,
            'session_token' => CheckoutSession::generateSessionToken(),
            'status' => 'started',
            'current_step' => 'identification',
            'customer_name' => null,
            'customer_email' => null,
            'customer_document' => null,
            'customer_document_type' => null,
            'customer_phone' => null,
            'customer_birth_date' => null,
            'zipcode' => null,
            'street' => null,
            'number' => null,
            'complement' => null,
            'neighborhood' => null,
            'city' => null,
            'state' => null,
            'recipient_name' => null,
            'delivery_zipcode' => null,
            'delivery_street' => null,
            'delivery_number' => null,
            'delivery_complement' => null,
            'delivery_neighborhood' => null,
            'delivery_city' => null,
            'delivery_state' => null,
            'delivery_recipient_name' => null,
            'payment_method' => null,
            'shipping_option_id' => null,
            'shipping_option_name' => null,
            'subtotal' => 100.00,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => 100.00,
            'last_activity_at' => now(),
        ], $overrides));
    }

    private function makeShippingOption(User $seller, array $overrides = []): CheckoutShippingOption
    {
        return CheckoutShippingOption::query()->create(array_merge([
            'seller_id' => $seller->id,
            'name' => 'Frete padrão',
            'price' => 0,
            'eta_days' => 5,
            'is_default' => false,
            'is_active' => true,
        ], $overrides));
    }
}
