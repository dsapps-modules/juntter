<?php

namespace Tests\Feature;

use App\Models\CheckoutLink;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SellerCheckoutLinkLatestStyleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_latest_style_endpoint_returns_the_most_recent_checkout_link_for_the_authenticated_seller(): void
    {
        Storage::fake('public');

        $seller = User::factory()->create([
            'nivel_acesso' => 'vendedor',
        ]);

        $olderProduct = Product::query()->create([
            'seller_id' => $seller->id,
            'name' => 'Produto antigo',
            'slug' => 'produto-antigo',
            'description' => 'Descrição antiga',
            'price' => 149.90,
            'status' => 'active',
        ]);

        $newerProduct = Product::query()->create([
            'seller_id' => $seller->id,
            'name' => 'Produto novo',
            'slug' => 'produto-novo',
            'description' => 'Descrição nova',
            'price' => 249.90,
            'status' => 'active',
        ]);

        Storage::disk('public')->put('checkout-links/latest-style.jpg', 'fake-image-content');

        CheckoutLink::query()->create([
            'seller_id' => $seller->id,
            'product_id' => $olderProduct->id,
            'public_token' => CheckoutLink::generatePublicToken(),
            'name' => 'Checkout antigo',
            'status' => 'active',
            'quantity' => 1,
            'unit_price' => 149.90,
            'total_price' => 149.90,
            'allow_pix' => true,
            'allow_boleto' => true,
            'allow_credit_card' => true,
            'request_address' => true,
            'pix_discount_type' => 'none',
            'pix_discount_value' => 0,
            'boleto_discount_type' => 'none',
            'boleto_discount_value' => 0,
            'free_shipping' => true,
            'product_image_path' => null,
            'visual_config' => [
                'theme' => 'noir',
                'store_name' => 'Loja Antiga',
                'primary_color' => '#111111',
                'navbar_background_color' => '#222222',
                'navbar_text_color' => '#eeeeee',
                'button_text_color' => '#ffffff',
                'offer_message' => 'Oferta antiga',
                'footer_text' => 'Rodapé antigo',
            ],
        ]);

        $latestCheckoutLink = CheckoutLink::query()->create([
            'seller_id' => $seller->id,
            'product_id' => $newerProduct->id,
            'public_token' => CheckoutLink::generatePublicToken(),
            'name' => 'Checkout mais recente',
            'status' => 'active',
            'quantity' => 2,
            'unit_price' => 249.90,
            'total_price' => 499.80,
            'allow_pix' => true,
            'allow_boleto' => false,
            'allow_credit_card' => true,
            'request_address' => true,
            'pix_discount_type' => 'none',
            'pix_discount_value' => 0,
            'boleto_discount_type' => 'none',
            'boleto_discount_value' => 0,
            'free_shipping' => true,
            'product_image_path' => 'checkout-links/latest-style.jpg',
            'visual_config' => [
                'theme' => 'atlantic',
                'store_name' => 'Loja Atual',
                'primary_color' => '#147D82',
                'navbar_background_color' => '#FFFFFF',
                'navbar_text_color' => '#123B59',
                'button_text_color' => '#FFFFFF',
                'offer_message' => 'Oferta atual',
                'footer_text' => 'Rodapé atual',
            ],
        ]);

        $response = $this->actingAs($seller)->getJson('/seller/checkout-links/ultimo-estilo');

        $response->assertOk();
        $response->assertJsonPath('checkout_link.id', $latestCheckoutLink->id);
        $response->assertJsonPath('checkout_link.visual_config.theme', 'atlantic');
        $response->assertJsonPath('checkout_link.visual_config.store_name', 'Loja Atual');
        $response->assertJsonPath('checkout_link.visual_config.primary_color', '#147D82');
        $response->assertJsonPath('checkout_link.visual_config.offer_message', 'Oferta atual');
        $response->assertJsonPath('checkout_link.visual_config.footer_text', 'Rodapé atual');
        $response->assertJsonPath('checkout_link.product_image_path', 'checkout-links/latest-style.jpg');
        $response->assertJsonPath('checkout_link.product_image_url', route('checkout.public.product-image', $latestCheckoutLink->public_token));
    }

    public function test_the_latest_style_endpoint_returns_not_found_when_there_is_no_previous_checkout(): void
    {
        $seller = User::factory()->create([
            'nivel_acesso' => 'vendedor',
        ]);

        $response = $this->actingAs($seller)->getJson('/seller/checkout-links/ultimo-estilo');

        $response->assertNotFound();
        $response->assertJsonPath('message', 'Nenhum checkout anterior foi encontrado.');
    }
}
