<?php

namespace Tests\Feature;

use App\Models\LinkPagamento;
use App\Models\PaytimeEstablishment;
use App\Models\User;
use App\Services\PaytimePricingCacheService;
use App\Services\PixService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PagamentoClienteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_payment_page_uses_the_company_logo_when_available(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('company-logos/logo-publico.png', 'fake-image-content');

        $user = $this->makeVendorUser('155161', 'company-logos/logo-publico.png');
        $link = $this->makeLinkPagamento('155161');

        $response = $this->get(route('pagamento.link', $link->codigo_unico));

        $response->assertOk();
        $response->assertSee('<link rel="icon" href="/company-logo?path=company-logos%2Flogo-publico.png"', false);
        $response->assertSee('<link rel="shortcut icon" href="/company-logo?path=company-logos%2Flogo-publico.png"', false);
        $response->assertSee('/company-logo?path=company-logos%2Flogo-publico.png', false);
        $response->assertSee("onerror=\"this.onerror=null;this.src='/img/logo/juntter_webp_640_174.webp';\"", false);
    }

    public function test_public_payment_page_falls_back_to_default_logo_when_company_logo_is_missing(): void
    {
        $this->makeVendorUser('155161');
        $link = $this->makeLinkPagamento('155161');

        $response = $this->get(route('pagamento.link', $link->codigo_unico));

        $response->assertOk();
        $response->assertSee('<link rel="icon" href="/img/logo/juntter_webp_640_174.webp"', false);
        $response->assertSee('<link rel="shortcut icon" href="/img/logo/juntter_webp_640_174.webp"', false);
        $response->assertSee('/img/logo/juntter_webp_640_174.webp', false);
        $response->assertSee('Checkout', false);
        $response->assertDontSee('<span>Pagamento</span>', false);
        $response->assertDontSee('<span>Confirmação</span>', false);
        $response->assertDontSee('security-badges', false);
        $response->assertDontSee('SSL Seguro', false);
        $response->assertDontSee('Dados Protegidos', false);
        $response->assertSee('window.JuntterRoutes', false);
        $response->assertSee(route('pagamento.sucesso'), false);
        $response->assertSee(route('pagamento.erro'), false);
    }

    public function test_payment_success_page_is_a_full_confirmation_screen(): void
    {
        $response = $this->get(route('pagamento.sucesso', [
            'seller_brand_mode' => 'logo',
            'seller_brand_label' => 'Empresa Exemplo',
            'seller_brand_logo_url' => '/company-logo?path=company-logos%2Flogo-publico.png',
        ]));

        $response->assertOk();
        $response->assertSee('<link rel="icon" href="/company-logo?path=company-logos%2Flogo-publico.png"', false);
        $response->assertSee('<link rel="shortcut icon" href="/company-logo?path=company-logos%2Flogo-publico.png"', false);
        $response->assertSee('Pagamento confirmado', false);
        $response->assertSee('Obrigado pela compra', false);
        $response->assertSee('Voltar para o início', false);
        $response->assertSee('payment-success-card', false);
    }

    public function test_payment_error_page_is_a_failure_screen(): void
    {
        $response = $this->get(route('pagamento.erro', [
            'seller_brand_mode' => 'logo',
            'seller_brand_label' => 'Empresa Exemplo',
            'seller_brand_logo_url' => '/company-logo?path=company-logos%2Flogo-publico.png',
            'message' => 'Erro ao processar pagamento.',
        ]));

        $response->assertOk();
        $response->assertSee('Pagamento não concluído', false);
        $response->assertSee('Não foi possível concluir o pagamento', false);
        $response->assertSee('Erro ao processar pagamento.', false);
        $response->assertSee('Tentar novamente', false);
    }

    public function test_payment_error_page_uses_the_seller_logo_for_the_favicon_when_available(): void
    {
        $response = $this->get(route('pagamento.erro', [
            'seller_brand_mode' => 'logo',
            'seller_brand_label' => 'Empresa Exemplo',
            'seller_brand_logo_url' => '/company-logo?path=company-logos%2Flogo-publico.png',
            'message' => 'Erro ao processar pagamento.',
        ]));

        $response->assertOk();
        $response->assertSee('<link rel="icon" href="/company-logo?path=company-logos%2Flogo-publico.png"', false);
        $response->assertSee('<link rel="shortcut icon" href="/company-logo?path=company-logos%2Flogo-publico.png"', false);
    }

    public function test_public_payment_page_shows_customer_fee_in_the_pix_total(): void
    {
        $this->makeVendorUser('155161');
        $this->makePricingSnapshot('155161', 1.00);
        $link = $this->makePixLink('155161', 'CLIENT', 6000);

        $response = $this->get(route('pagamento.link', $link->codigo_unico));

        $response->assertOk();
        $response->assertSee('Valor', false);
        $response->assertSee('Taxa do Pix', false);
        $response->assertSee('R$ 0,60', false);
        $response->assertSee('Total', false);
        $response->assertSee('R$ 60,60', false);
    }

    public function test_public_payment_page_uses_the_pix_fee_from_the_pricing_cache(): void
    {
        $this->makeVendorUser('155163');
        $link = $this->makePixLink('155163', 'CLIENT', 6000);

        $this->mock(PaytimePricingCacheService::class, function ($mock): void {
            $mock->shouldReceive('resolvePixIncomingFeeCents')
                ->once()
                ->with('155163', 6000)
                ->andReturn(60);
        });

        $response = $this->get(route('pagamento.link', $link->codigo_unico));

        $response->assertOk();
        $response->assertSee('Taxa do Pix', false);
        $response->assertSee('R$ 0,60', false);
        $response->assertSee('R$ 60,60', false);
    }

    public function test_public_payment_page_for_credit_card_hides_the_total_and_places_installments_after_card_fields(): void
    {
        $this->makeVendorUser('155164');
        $this->makePricingSnapshot('155164', 1.00, [
            [
                'id' => 2,
                'name' => 'VISA',
                'active' => true,
                'fees' => [
                    'credit' => [
                        '1x' => 0.00,
                        '2x' => 2.50,
                        '3x' => 4.00,
                    ],
                ],
            ],
        ]);

        $link = LinkPagamento::query()->create([
            'estabelecimento_id' => '155164',
            'codigo_unico' => LinkPagamento::gerarCodigoUnico(),
            'descricao' => 'Link cartão público',
            'valor' => 120.00,
            'valor_centavos' => 12000,
            'parcelas' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'CARTAO',
        ]);

        $response = $this->get(route('pagamento.link', $link->codigo_unico));

        $response->assertOk();
        $response->assertSee('id="cardBrandPreview"', false);
        $response->assertSee('name="card_brand"', false);
        $response->assertSee('id="installmentsSelect"', false);
        $response->assertSee('carregar as parcelas', false);
        $response->assertSee('credit_card_pricing', false);
        $response->assertDontSee('<span class="order-item-label">Total</span>', false);
        $response->assertSeeInOrder([
            'name="card[expiration_month]"',
            'name="card[expiration_year]"',
            'name="card[security_code]"',
            'id="installmentsSelect"',
        ], false);
    }

    public function test_processar_pix_applies_customer_fee_when_link_charges_client(): void
    {
        $this->makeVendorUser('155161');
        $this->makePricingSnapshot('155161', 1.00);
        $link = $this->makePixLink('155161', 'CLIENT', 6000);

        $capturedPayload = [];

        $this->mock(PixService::class, function ($mock) use (&$capturedPayload): void {
            $mock->shouldReceive('criarTransacaoPix')
                ->once()
                ->with(\Mockery::on(function (array $payload) use (&$capturedPayload): bool {
                    $capturedPayload = $payload;

                    return true;
                }))
                ->andReturn([
                    '_id' => 'pix_123',
                    'status' => 'PENDING',
                    'amount' => 6060,
                    'emv' => '000201010212',
                ]);

            $mock->shouldReceive('obterQrCodePix')
                ->once()
                ->with('pix_123')
                ->andReturn([
                    'qrcode' => 'base64-image',
                    'emv' => '000201010212',
                ]);
        });

        $response = $this->postJson('/pagamento/'.$link->codigo_unico.'/pix');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('pix_data.amount', 6060)
            ->assertJsonPath('pix_data.status', 'PENDING');

        $this->assertSame(6060, $capturedPayload['amount'] ?? null);
        $this->assertSame('CLIENT', $capturedPayload['interest'] ?? null);
    }

    public function test_processar_pix_keeps_base_amount_when_establishment_pays_fees(): void
    {
        $this->makeVendorUser('155162');
        $this->makePricingSnapshot('155162', 1.00);
        $link = $this->makePixLink('155162', 'ESTABLISHMENT', 6000);

        $capturedPayload = [];

        $this->mock(PixService::class, function ($mock) use (&$capturedPayload): void {
            $mock->shouldReceive('criarTransacaoPix')
                ->once()
                ->with(\Mockery::on(function (array $payload) use (&$capturedPayload): bool {
                    $capturedPayload = $payload;

                    return true;
                }))
                ->andReturn([
                    '_id' => 'pix_456',
                    'status' => 'PENDING',
                    'amount' => 6000,
                    'emv' => '000201010212',
                ]);

            $mock->shouldReceive('obterQrCodePix')
                ->once()
                ->with('pix_456')
                ->andReturn([
                    'qrcode' => 'base64-image',
                    'emv' => '000201010212',
                ]);
        });

        $response = $this->postJson('/pagamento/'.$link->codigo_unico.'/pix');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('pix_data.amount', 6000)
            ->assertJsonPath('pix_data.status', 'PENDING');

        $this->assertSame(6000, $capturedPayload['amount'] ?? null);
        $this->assertSame('ESTABLISHMENT', $capturedPayload['interest'] ?? null);
    }

    private function makeVendorUser(string $establishmentId, ?string $companyLogoPath = null): User
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
            'company_logo_path' => $companyLogoPath,
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => $establishmentId,
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]);

        return $user;
    }

    private function makeLinkPagamento(string $establishmentId): LinkPagamento
    {
        return LinkPagamento::query()->create([
            'estabelecimento_id' => $establishmentId,
            'codigo_unico' => LinkPagamento::gerarCodigoUnico(),
            'descricao' => 'Link público de teste',
            'valor' => 10.00,
            'valor_centavos' => 1000,
            'parcelas' => [1],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'CARTAO',
        ]);
    }

    public function test_public_payment_page_syncs_contracted_plan_pricing_when_credit_fees_are_not_cached(): void
    {
        $this->makeVendorUser('155165');
        $link = $this->makeLinkPagamento('155165');

        $plan = [
            'id' => 23025,
            'name' => 'Plano Economico D1 Online',
            'active' => true,
            'modality' => 'ONLINE',
            'flags' => [
                [
                    'id' => 2,
                    'name' => 'VISA',
                    'active' => true,
                    'fees' => [
                        'credit' => [
                            '1x' => 0.00,
                            '2x' => 2.50,
                            '3x' => 4.00,
                        ],
                    ],
                ],
            ],
        ];

        $this->mock(PaytimePricingCacheService::class, function ($mock) use ($plan): void {
            $mock->shouldReceive('resolveContractedPlan')
                ->twice()
                ->andReturn(null, $plan);

            $mock->shouldReceive('syncEstablishmentPricing')
                ->once()
                ->with('155165')
                ->andReturnNull();

            $mock->shouldReceive('syncContractedPlanPricing')
                ->once()
                ->with('155165')
                ->andReturnNull();

            $mock->shouldReceive('resolvePixIncomingFeeCents')
                ->once()
                ->with('155165', 1000)
                ->andReturn(0);
        });

        $response = $this->get(route('pagamento.link', $link->codigo_unico));

        $response->assertOk();
        $response->assertSee('credit_card_pricing', false);
        $response->assertSee('VISA', false);
    }

    private function makePricingSnapshot(string $establishmentId, float $pixFeePercent, array $creditFlags = []): PaytimeEstablishment
    {
        return PaytimeEstablishment::query()->create([
            'id' => (int) $establishmentId,
            'first_name' => 'Estabelecimento',
            'active' => true,
            'status' => 'APPROVED',
            'contracted_plan_json' => [
                'id' => 23025,
                'name' => 'Plano Economico D1 Online',
                'active' => true,
                'modality' => 'ONLINE',
                'flags' => [
                    [
                        'id' => 1,
                        'name' => 'BACEN',
                        'active' => true,
                        'fees' => [
                            'pix' => $pixFeePercent,
                            'dynamic_pix' => $pixFeePercent,
                        ],
                    ],
                    ...$creditFlags,
                ],
            ],
            'fees_banking_json' => [
                [
                    'id' => 8,
                    'fees' => [
                        'pix' => 365,
                        'dynamic_pix' => 365,
                    ],
                ],
            ],
        ]);
    }

    private function makePixLink(string $establishmentId, string $juros, int $valorCentavos = 4500): LinkPagamento
    {
        return LinkPagamento::query()->create([
            'estabelecimento_id' => $establishmentId,
            'codigo_unico' => LinkPagamento::gerarCodigoUnico(),
            'descricao' => 'Pix Maluco',
            'valor' => $valorCentavos / 100,
            'valor_centavos' => $valorCentavos,
            'parcelas' => [1],
            'juros' => $juros,
            'status' => 'ATIVO',
            'tipo_pagamento' => 'PIX',
            'dados_cliente' => [
                'nome_obrigatorio' => false,
                'email_obrigatorio' => false,
                'telefone_obrigatorio' => false,
                'documento_obrigatorio' => false,
                'endereco_obrigatorio' => false,
                'preenchidos' => [],
            ],
        ]);
    }
}
