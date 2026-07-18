<?php

namespace Tests\Feature;

use App\Models\CheckoutLink;
use App\Models\CheckoutSession;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCheckoutStepFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_identification_step_redirects_to_the_spa_checkout(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
        $session = $this->makeCheckoutSession($link);

        $response = $this->postJson(route('checkout.public.identification', $session->session_token), [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document_type' => 'cpf',
            'customer_document' => '12345678909',
            'customer_phone' => '11999999999',
            'customer_birth_date' => '1990-01-01',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'complement' => 'Apto 1',
            'neighborhood' => 'Centro',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
        ]);

        $response->assertOk();
        $response->assertJsonPath('next_url', route('checkout.public.delivery.page', $session->session_token));
        $response->assertJsonPath('checkout_session.current_step', 'delivery');
        $response->assertJsonPath('checkout_session.session_token', $session->session_token);
    }

    public function test_delivery_page_redirects_to_the_spa_checkout(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
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
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'current_step' => 'delivery',
        ]);

        $response = $this->get(route('checkout.public.delivery.page', $session->session_token));

        $response->assertRedirect(route('checkout.public.spa.show', $link->public_token));
    }

    public function test_payment_page_redirects_to_the_spa_checkout(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller), [
            'allow_pix' => true,
            'allow_boleto' => true,
            'allow_credit_card' => false,
        ]);
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
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'current_step' => 'payment',
        ]);

        $response = $this->get(route('checkout.public.payment.page', $session->session_token));

        $response->assertRedirect(route('checkout.public.spa.show', $link->public_token));
    }

    public function test_payment_details_page_redirects_to_the_spa_checkout(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller), [
            'allow_pix' => true,
            'allow_boleto' => true,
            'allow_credit_card' => true,
        ]);
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
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'current_step' => 'payment',
            'payment_method' => 'credit_card',
        ]);

        $response = $this->get(route('checkout.public.payment.details', $session->session_token));

        $response->assertRedirect(route('checkout.public.spa.show', $link->public_token));
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
            'subtotal' => 100.00,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => 100.00,
            'last_activity_at' => now(),
        ], $overrides));
    }
}
