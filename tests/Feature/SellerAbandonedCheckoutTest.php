<?php

namespace Tests\Feature;

use App\Models\AbandonedCheckoutRecovery;
use App\Models\CheckoutLink;
use App\Models\CheckoutSession;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerAbandonedCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_abandoned_checkout_menu_item_and_spa_route_are_present(): void
    {
        $navigationSource = file_get_contents(base_path('resources/js/spa/navigation/menu.js'));
        $appSource = file_get_contents(base_path('resources/js/spa/App.jsx'));
        $shellSource = file_get_contents(base_path('resources/js/spa/layouts/AppShell.jsx'));

        $this->assertNotFalse($navigationSource);
        $this->assertNotFalse($appSource);
        $this->assertNotFalse($shellSource);
        $this->assertStringContainsString("label: 'Carrinho abandonado'", $navigationSource);
        $this->assertStringContainsString("path: '/seller/checkout-links/abandonados'", $navigationSource);
        $this->assertStringContainsString("icon: 'checkout-abandoned'", $navigationSource);
        $this->assertStringContainsString("import CheckoutAbandonedPage from './pages/checkout/CheckoutAbandonedPage';", $appSource);
        $this->assertStringContainsString('<Route path="seller/checkout-links/abandonados" element={<CheckoutAbandonedPage />} />', $appSource);
        $this->assertStringContainsString('ShoppingCartOutlined', $shellSource);

        $checkoutLinksPosition = strpos($navigationSource, "key: 'checkout.links'");
        $abandonedPosition = strpos($navigationSource, "key: 'checkout.abandoned'");
        $shippingPosition = strpos($navigationSource, "key: 'checkout.frete'");

        $this->assertNotFalse($checkoutLinksPosition);
        $this->assertNotFalse($abandonedPosition);
        $this->assertNotFalse($shippingPosition);
        $this->assertLessThan($abandonedPosition, $checkoutLinksPosition);
        $this->assertLessThan($shippingPosition, $abandonedPosition);
    }

    public function test_vendor_can_list_abandoned_checkout_sessions(): void
    {
        $seller = $this->makeVendorUser();
        $link = $this->makeCheckoutLink($seller, $this->makeProduct($seller));
        $session = $this->makeAbandonedCheckoutSession($link, [
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_phone' => '11999999999',
            'last_activity_at' => now()->subHours(3),
        ]);

        AbandonedCheckoutRecovery::query()->create([
            'checkout_session_id' => $session->id,
            'seller_id' => $seller->id,
            'channel' => 'email',
            'sequence_step' => 1,
            'status' => 'sent',
            'scheduled_at' => now()->subHours(2),
            'sent_at' => now()->subHours(2),
        ]);

        AbandonedCheckoutRecovery::query()->create([
            'checkout_session_id' => $session->id,
            'seller_id' => $seller->id,
            'channel' => 'email',
            'sequence_step' => 2,
            'status' => 'pending',
            'scheduled_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($seller)->getJson('/seller/checkout-links/abandonados');

        $response->assertOk();
        $response->assertJsonPath('abandoned_sessions.0.customer_name', 'Maria Silva');
        $response->assertJsonPath('abandoned_sessions.0.customer_email', 'maria@example.com');
        $response->assertJsonPath('abandoned_sessions.0.product_name', $link->product->name);
        $response->assertJsonPath('abandoned_sessions.0.sent_recoveries_count', 1);
        $response->assertJsonPath('abandoned_sessions.0.pending_recoveries_count', 1);
        $response->assertJsonPath('abandoned_sessions.0.recovery_status', 'in_progress');
    }

    public function test_the_abandoned_checkout_spa_route_is_available(): void
    {
        $seller = $this->makeVendorUser();
        $this->actingAs($seller);

        $response = $this->get('/app/seller/checkout-links/abandonados');

        $response->assertOk();
        $response->assertSee('id="app"', false);
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

    private function makeCheckoutLink(User $seller, Product $product): CheckoutLink
    {
        return CheckoutLink::query()->create([
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
        ]);
    }

    private function makeAbandonedCheckoutSession(CheckoutLink $link, array $overrides = []): CheckoutSession
    {
        return CheckoutSession::query()->create(array_merge([
            'checkout_link_id' => $link->id,
            'seller_id' => $link->seller_id,
            'product_id' => $link->product_id,
            'quantity' => $link->quantity,
            'session_token' => CheckoutSession::generateSessionToken(),
            'status' => 'abandoned',
            'current_step' => 'confirmation',
            'subtotal' => 100.00,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => 100.00,
            'last_activity_at' => now()->subHours(3),
        ], $overrides));
    }
}
