<?php

namespace Tests\Feature;

use App\Models\CheckoutLink;
use App\Models\CheckoutSession;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCheckoutPixLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_pix_payment_details_page_uses_a_single_main_card_for_the_payment_content(): void
    {
        $seller = $this->makeVendorUser();
        $product = $this->makeProduct($seller);
        $link = $this->makeCheckoutLink($seller, $product);
        $session = $this->makeCheckoutSession($link);

        $order = Order::query()->create([
            'seller_id' => $link->seller_id,
            'checkout_link_id' => $link->id,
            'checkout_session_id' => $session->id,
            'product_id' => $link->product_id,
            'order_number' => 'JNT-2026-000999',
            'status' => 'pending',
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

        PaymentTransaction::query()->create([
            'order_id' => $order->id,
            'seller_id' => $seller->id,
            'gateway' => 'paytime',
            'gateway_transaction_id' => 'pix-checkout-123',
            'gateway_status' => 'PENDING',
            'internal_status' => 'pending',
            'payment_method' => 'pix',
            'amount' => 100.00,
            'pix_copy_paste' => '00020126580014br.gov.bcb.pix...',
            'response_payload' => [
                'pix_copy_paste' => '00020126580014br.gov.bcb.pix...',
                'pix_qr_code_image' => 'data:image/png;base64,ZmFrZQ==',
                'api_qrcode' => [
                    'qrcode' => 'data:image/png;base64,ZmFrZQ==',
                ],
            ],
        ]);

        $response = $this->get(route('checkout.public.payment.details', $session->session_token));

        $response->assertOk();
        $response->assertSee('Pagamento', false);
        $response->assertSee('Alterar', false);

        $response->assertSee('Escaneie o QR Code', false);
        $response->assertSee('QR Code Pix', false);
        $response->assertSee('Resumo do pedido', false);
        $response->assertDontSee('Desconto', false);
        $response->assertDontSee('Frete', false);
        $response->assertDontSee('Status', false);
        $response->assertDontSee('Expira em', false);
        $response->assertDontSee('Aguardando', false);
        $response->assertDontSee('Pagar', false);
        $response->assertDontSee('Selecione o metodo de pagamento', false);
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
            'description' => 'Descricao do produto',
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

    private function makeCheckoutSession(CheckoutLink $link): CheckoutSession
    {
        return CheckoutSession::query()->create([
            'checkout_link_id' => $link->id,
            'seller_id' => $link->seller_id,
            'product_id' => $link->product_id,
            'quantity' => 1,
            'session_token' => CheckoutSession::generateSessionToken(),
            'status' => 'payment_pending',
            'current_step' => 'payment',
            'customer_name' => 'Maria Silva',
            'customer_email' => 'maria@example.com',
            'customer_document' => '12345678909',
            'customer_document_type' => 'cpf',
            'customer_phone' => '11999999999',
            'zipcode' => '01001000',
            'street' => 'Rua A',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'recipient_name' => 'Maria Silva',
            'subtotal' => 100.00,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => 100.00,
            'last_activity_at' => now(),
            'payment_method' => 'pix',
        ]);
    }
}
