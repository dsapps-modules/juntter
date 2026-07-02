<?php

namespace Tests\Unit;

use App\Http\Requests\LinkBoletoRequest;
use App\Http\Requests\StartCheckoutPaymentRequest;
use App\Http\Requests\StoreCheckoutLinkRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CheckoutLinkRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_link_validation_messages_use_user_friendly_labels(): void
    {
        $seller = User::factory()->create();
        $product = Product::query()->create([
            'seller_id' => $seller->id,
            'name' => 'Produto teste',
            'slug' => 'produto-teste-'.random_int(1000, 9999),
            'description' => null,
            'short_description' => null,
            'sku' => null,
            'image_path' => null,
            'price' => 10.00,
            'status' => 'active',
        ]);

        $validator = Validator::make([
            'product_id' => $product->id,
            'name' => 'Link promocional',
            'status' => 'active',
            'quantity' => 1,
            'unit_price' => 0,
        ], (new StoreCheckoutLinkRequest)->rules());

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('preço unitário', mb_strtolower($validator->errors()->first('unit_price')));
        $this->assertStringNotContainsString('unit_price', $validator->errors()->first('unit_price'));
    }

    public function test_nested_checkout_fields_use_user_friendly_labels(): void
    {
        $paymentValidator = Validator::make([
            'payment_method' => 'credit_card',
            'installments' => 1,
            'card' => [
                'holder_name' => 'Maria Silva',
                'holder_document' => '123.456.789-09',
                'expiration_month' => 7,
                'expiration_year' => now()->year + 1,
                'security_code' => '123',
            ],
        ], (new StartCheckoutPaymentRequest)->rules());

        $boletoValidator = Validator::make([
            'descricao' => 'Cobrança teste',
            'valor' => '10,00',
            'data_vencimento' => now()->addDay()->toDateString(),
            'juros' => 'CLIENT',
            'dados_cliente_preenchidos' => [
                'nome' => 'Maria',
                'sobrenome' => 'Silva',
                'email' => 'maria@example.com',
                'telefone' => '(11) 99999-9999',
                'endereco' => [
                    'rua' => 'Rua A',
                    'numero' => '100',
                    'bairro' => 'Centro',
                    'cidade' => 'São Paulo',
                    'estado' => 'SP',
                    'cep' => '01001-000',
                ],
            ],
            'instrucoes_boleto' => [
                'late_fee' => ['amount' => '2,00'],
                'interest' => ['amount' => '1,00'],
                'discount' => [
                    'amount' => '5,00',
                    'limit_date' => now()->toDateString(),
                ],
            ],
        ], (new LinkBoletoRequest)->rules());

        $this->assertTrue($paymentValidator->fails());
        $this->assertStringContainsString('número do cartão', mb_strtolower($paymentValidator->errors()->first('card.card_number')));
        $this->assertStringNotContainsString('card.card_number', $paymentValidator->errors()->first('card.card_number'));

        $this->assertTrue($boletoValidator->fails());
        $this->assertStringContainsString('documento do cliente', mb_strtolower($boletoValidator->errors()->first('dados_cliente_preenchidos.documento')));
        $this->assertStringNotContainsString('dados_cliente_preenchidos.documento', $boletoValidator->errors()->first('dados_cliente_preenchidos.documento'));
    }

    public function test_checkout_link_request_accepts_the_new_visual_color_fields(): void
    {
        $rules = (new StoreCheckoutLinkRequest)->rules();

        $this->assertArrayHasKey('visual_config.navbar_background_color', $rules);
        $this->assertArrayHasKey('visual_config.navbar_text_color', $rules);
        $this->assertArrayHasKey('visual_config.button_text_color', $rules);
    }
}
