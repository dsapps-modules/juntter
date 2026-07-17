<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerCheckoutShippingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_checkout_shipping_menu_item_and_spa_route_are_present(): void
    {
        $navigationSource = file_get_contents(base_path('resources/js/spa/navigation/menu.js'));
        $appSource = file_get_contents(base_path('resources/js/spa/App.jsx'));

        $this->assertNotFalse($navigationSource);
        $this->assertNotFalse($appSource);
        $this->assertStringContainsString("label: 'Configurar Frete'", $navigationSource);
        $this->assertStringContainsString("path: '/seller/checkout-links/frete'", $navigationSource);
        $this->assertStringContainsString('seller/checkout-links/frete', $appSource);
    }

    public function test_vendor_can_create_update_and_delete_shipping_options(): void
    {
        $seller = $this->makeVendorUser();
        $this->actingAs($seller);

        $createResponse = $this->postJson('/seller/checkout-links/frete', [
            'name' => 'Frete padrão',
            'price' => 0,
            'eta_days' => 5,
            'is_default' => true,
            'is_active' => true,
        ]);

        $createResponse->assertCreated();
        $shippingOptionId = $createResponse->json('shipping_option.id');

        $this->assertDatabaseHas('checkout_shipping_options', [
            'id' => $shippingOptionId,
            'seller_id' => $seller->id,
            'name' => 'Frete padrão',
            'is_default' => true,
            'is_active' => true,
        ]);

        $updateResponse = $this->putJson('/seller/checkout-links/frete/'.$shippingOptionId, [
            'name' => 'Frete expresso',
            'price' => 19.9,
            'eta_days' => 2,
            'is_default' => false,
            'is_active' => true,
        ]);

        $updateResponse->assertOk();

        $this->assertDatabaseHas('checkout_shipping_options', [
            'id' => $shippingOptionId,
            'seller_id' => $seller->id,
            'name' => 'Frete expresso',
            'eta_days' => 2,
            'is_active' => true,
        ]);

        $deleteResponse = $this->deleteJson('/seller/checkout-links/frete/'.$shippingOptionId);

        $deleteResponse->assertOk();
        $this->assertDatabaseMissing('checkout_shipping_options', [
            'id' => $shippingOptionId,
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
}
