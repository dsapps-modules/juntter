<?php

namespace Tests\Feature;

use App\Models\CheckoutLink;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCheckoutSpaTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_checkout_link_response_includes_the_spa_url(): void
    {
        $user = $this->makeVendorUser();
        $product = $this->makeProduct($user);

        $response = $this->actingAs($user)->postJson('/seller/checkout-links', [
            'product_id' => $product->id,
            'name' => 'Oferta principal',
            'status' => 'active',
            'quantity' => 1,
            'unit_price' => 149.90,
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
                'navbar_background_color' => '#ffffff',
            ],
        ]);

        $response->assertCreated();
        $publicToken = $response->json('checkout_link.public_token');

        $this->assertSame(route('checkout.public.spa.show', $publicToken), $response->json('checkout_link.public_spa_url'));
    }

    public function test_public_checkout_spa_page_renders_the_initial_payload(): void
    {
        $user = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($user, $this->makeProduct($user), [
            'allow_pix' => true,
            'allow_boleto' => true,
            'allow_credit_card' => true,
            'request_address' => true,
            'visual_config' => [
                'store_name' => 'Loja Teste',
                'primary_color' => '#111827',
                'navbar_background_color' => '#ffffff',
                'offer_message' => 'Oferta especial',
                'footer_text' => 'Atendimento em horário comercial.',
            ],
        ]);

        $response = $this->get(route('checkout.public.spa.show', $link->public_token));

        $response->assertOk();
        $response->assertSee('checkout-spa-body', false);
        $response->assertSee('checkout-spa-root', false);
        $response->assertSee('checkout-spa-data', false);
        $response->assertDontSee('Checkout SPA', false);
        $response->assertDontSee('Uma versão alternativa do checkout com navegação em uma única tela.', false);
        $response->assertSee('allow_pix', false);
        $response->assertSee('allow_boleto', false);
        $response->assertSee('allow_credit_card', false);
        $response->assertSee('request_address', false);
        $response->assertSee('visual_config', false);
        $response->assertSee('checkoutPageMode', false);
        $response->assertSee('threeDsEnv', false);
        $response->assertSee('paymentDetails', false);
        $response->assertSee('Descrição do produto', false);
        $response->assertDontSee(':5173', false);
        $response->assertSee('build/assets/checkout-spa', false);
        $response->assertSee(route('checkout.public.spa.show', $link->public_token), false);
    }

    public function test_checkout_spa_source_includes_the_3ds_flow(): void
    {
        $source = file_get_contents(resource_path('js/checkout-spa.jsx'));
        $styles = file_get_contents(resource_path('css/checkout-spa.css'));

        $this->assertNotFalse($source);
        $this->assertNotFalse($styles);
        $this->assertStringContainsString('authenticate3DS', $source);
        $this->assertStringContainsString('confirmCreditCard3DS', $source);
        $this->assertStringContainsString('antifraudAuthTemplate', $source);
        $this->assertStringContainsString('threeDsEnv', $source);
        $this->assertStringContainsString('Formas de pagamento', $source);
        $this->assertStringContainsString('lookupAddressByZipcode', $source);
        $this->assertStringContainsString('viacep.com.br/ws/${normalizeDigits(zipcode)}/json/', $source);
        $this->assertStringContainsString('Consultando CEP...', $source);
        $this->assertStringContainsString('checkout-spa-step-card--payment-details', $source);
        $this->assertStringContainsString('checkout-spa-step-card--payment-note', $source);
        $this->assertStringContainsString('Voltar para identificação', $source);
        $this->assertStringContainsString('Voltar para endereço', $source);
        $this->assertStringContainsString('Continuar para pagamento', $source);
        $this->assertStringContainsString('Voltar aos métodos', $source);
        $this->assertStringContainsString("step === 'payment-details' && allowedMethods.length > 1 && config?.urls?.choosePaymentMethod", $source);
        $this->assertStringContainsString('background: var(--checkout-spa-button, #ffffff);', $styles);
        $this->assertStringContainsString('color: var(--checkout-spa-button-ink, #17120d);', $styles);
        $this->assertStringContainsString('color: var(--checkout-spa-navbar-ink, #17120d);', $styles);
    }

    private function makeVendorUser(): User
    {
        $user = User::query()->create([
            'name' => 'Vendedor Teste',
            'email' => 'vendedor+'.random_int(1, 9999).'@example.com',
            'password' => bcrypt('password'),
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
                'navbar_background_color' => '#ffffff',
            ],
        ], $overrides));
    }
}
