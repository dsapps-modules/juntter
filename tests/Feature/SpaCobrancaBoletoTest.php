<?php

namespace Tests\Feature;

use App\Models\PaytimeEstablishment;
use App\Models\PaytimeTransaction;
use App\Models\User;
use App\Services\BoletoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaCobrancaBoletoTest extends TestCase
{
    use RefreshDatabase;

    public function test_cobranca_boleto_overview_returns_gateway_boletos(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => '5001',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]);

        $boletoService = $this->createMock(BoletoService::class);
        $boletoService->expects($this->once())
            ->method('listarBoletos')
            ->with($this->callback(function (array $filters): bool {
                $decoded = json_decode($filters['filters'] ?? '{}', true);

                return ($filters['perPage'] ?? null) === 1000
                    && ($filters['page'] ?? null) === 1
                    && ($decoded['establishment.id'] ?? null) === '5001';
            }))
            ->willReturn([
                'data' => [
                    [
                        '_id' => 'boleto-123',
                        'establishment' => ['id' => '5001', 'name' => 'CELCOIN'],
                        'status' => 'PENDING',
                        'amount' => 810,
                        'fees' => 190,
                        'url' => 'https://example.test/boleto-123.pdf',
                        'customer_name' => 'Reginaldo do Prado',
                        'created_at' => '2026-05-04 13:43:00',
                    ],
                    [
                        '_id' => 'boleto-456',
                        'establishment' => ['id' => '5001', 'name' => 'CELCOIN'],
                        'status' => 'PENDING',
                        'amount' => 910,
                        'fees' => 190,
                        'url' => 'https://example.test/boleto-456.pdf',
                        'customer_name' => 'Maria Cristina',
                        'created_at' => '2026-05-04 14:03:00',
                    ],
                ],
            ]);

        $this->app->instance(BoletoService::class, $boletoService);

        $response = $this->actingAs($user)->getJson('/api/spa/cobranca/boleto?period=2026-05');

        $response
            ->assertOk()
            ->assertJsonPath('summary.total_billets', 2)
            ->assertJsonPath('summary.pending_billets', 2)
            ->assertJsonPath('rows.0.code', 'boleto-456')
            ->assertJsonPath('rows.0.type', 'Boleto')
            ->assertJsonPath('rows.0.status', 'Pendente')
            ->assertJsonPath('rows.0.pdf_url', 'https://example.test/boleto-456.pdf')
            ->assertJsonPath('rows.1.code', 'boleto-123')
            ->assertJsonPath('rows.1.pdf_url', 'https://example.test/boleto-123.pdf');
    }

    public function test_cobranca_boleto_detail_returns_complete_gateway_data(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => '5001',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]);

        $boletoService = $this->createMock(BoletoService::class);
        $boletoService->expects($this->once())
            ->method('consultarBoleto')
            ->with('boleto-123')
            ->willReturn([
                '_id' => 'boleto-123',
                'status' => 'PENDING',
                'amount' => 810,
                'original_amount' => 1000,
                'fees' => 190,
                'gateway_key' => 'CELCOIN',
                'authorization_code' => 'CELCOIN',
                'created_at' => '2026-05-04 13:43:00',
                'updated_at' => '2026-05-04 13:45:00',
                'expiration_at' => '2026-05-08 12:00:00',
                'payment_limit_date' => '2026-05-09',
                'boleto_url' => 'https://example.test/boleto.pdf',
                'boleto_barcode' => '3419114400000001000109819643416091015649600',
                'boleto_digitable_line' => '34191098189643416091501564960001114400000001000',
                'client' => [
                    'first_name' => 'Reginaldo',
                    'last_name' => 'do Prado',
                    'document' => '09409616875',
                    'email' => 'reginaldo@example.test',
                ],
                'establishment' => [
                    'id' => '5001',
                    'name' => 'CELCOIN',
                ],
                'billing_instructions' => [
                    [
                        'name' => 'late_fee',
                        'mode' => 'PERCENTAGE',
                        'amount' => 1,
                        'limit_date' => '2026-05-07',
                    ],
                ],
            ]);
        $boletoService->expects($this->once())
            ->method('normalizarResposta')
            ->willReturnArgument(0);

        $this->app->instance(BoletoService::class, $boletoService);

        $response = $this->actingAs($user)->getJson('/api/spa/cobranca/boleto/boleto-123');

        $response
            ->assertOk()
            ->assertJsonPath('boleto.external_id', 'boleto-123')
            ->assertJsonPath('boleto.status_label', 'Pendente')
            ->assertJsonPath('boleto.boleto_url', 'https://example.test/boleto.pdf')
            ->assertJsonPath('boleto.juros', 'CLIENT')
            ->assertJsonPath('boleto.customer.first_name', 'Reginaldo')
            ->assertJsonPath('boleto.establishment.name', 'CELCOIN')
            ->assertJsonPath('boleto.billing_instructions.0.name', 'late_fee');
    }

    public function test_cobranca_boleto_detail_prefers_local_request_fee_payer_when_available(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => '5001',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]);

        PaytimeTransaction::query()->create([
            'external_id' => 'boleto-456',
            'establishment_id' => '5001',
            'type' => 'BILLET',
            'status' => 'PENDING',
            'amount' => 9850,
            'original_amount' => 10100,
            'fees' => 250,
            'installments' => 1,
            'metadata' => [
                'request' => [
                    'juros' => 'CLIENT',
                    'base_amount_cents' => 10000,
                    'charged_amount_cents' => 10250,
                    'tax_amount_cents' => 250,
                    'customer_fee_cents' => 250,
                ],
            ],
        ]);

        $boletoService = $this->createMock(BoletoService::class);
        $boletoService->expects($this->once())
            ->method('consultarBoleto')
            ->with('boleto-456')
            ->willReturn([
                '_id' => 'boleto-456',
                'status' => 'PENDING',
                'amount' => 9850,
                'original_amount' => 10100,
                'fees' => 250,
                'gateway_key' => 'CELCOIN',
                'authorization_code' => 'CELCOIN',
                'created_at' => '2026-05-04 13:43:00',
                'updated_at' => '2026-05-04 13:45:00',
                'expiration_at' => '2026-05-08 12:00:00',
                'payment_limit_date' => '2026-05-09',
                'boleto_url' => 'https://example.test/boleto.pdf',
                'boleto_barcode' => '3419114400000001000109819643416091015649600',
                'boleto_digitable_line' => '34191098189643416091501564960001114400000001000',
                'client' => [
                    'first_name' => 'Reginaldo',
                    'last_name' => 'do Prado',
                    'document' => '09409616875',
                    'email' => 'reginaldo@example.test',
                ],
                'establishment' => [
                    'id' => '5001',
                    'name' => 'CELCOIN',
                ],
                'billing_instructions' => [],
            ]);
        $boletoService->expects($this->once())
            ->method('normalizarResposta')
            ->willReturnArgument(0);

        $this->app->instance(BoletoService::class, $boletoService);

        $response = $this->actingAs($user)->getJson('/api/spa/cobranca/boleto/boleto-456');

        $response
            ->assertOk()
            ->assertJsonPath('boleto.juros', 'CLIENT')
            ->assertJsonPath('boleto.amount', 10000)
            ->assertJsonPath('boleto.original_amount', 10250)
            ->assertJsonPath('boleto.fees', 250);
    }

    public function test_cobranca_boleto_detail_uses_cached_fees_banking_when_available(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => '5001',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]);

        PaytimeEstablishment::query()->create([
            'id' => 5001,
            'type' => 'INDIVIDUAL',
            'first_name' => 'Isadora',
            'last_name' => 'Prado',
            'fantasy_name' => 'Loja Cache',
            'document' => '40400554895',
            'email' => 'isadora@example.test',
            'active' => true,
            'status' => 'APPROVED',
            'fees_banking_json' => [
                [
                    'id' => 8,
                    'name' => 'Pacote de tarifas 02',
                    'fees' => [
                        'pix' => 100,
                        'dynamic_pix' => 100,
                    ],
                ],
            ],
            'pricing_snapshot_json' => [
                'fees_banking' => [
                    [
                        'id' => 8,
                        'fees' => [
                            'pix' => 100,
                            'dynamic_pix' => 100,
                        ],
                    ],
                ],
            ],
            'pricing_snapshot_hash' => sha1('cached-boleto-fee'),
            'pricing_synced_at' => now(),
        ]);

        $boletoService = $this->createMock(BoletoService::class);
        $boletoService->expects($this->once())
            ->method('consultarBoleto')
            ->with('boleto-123')
            ->willReturn([
                '_id' => 'boleto-123',
                'status' => 'PENDING',
                'amount' => 810,
                'original_amount' => 1000,
                'fees' => 190,
                'gateway_key' => 'CELCOIN',
                'authorization_code' => 'CELCOIN',
                'created_at' => '2026-05-04 13:43:00',
                'updated_at' => '2026-05-04 13:45:00',
                'expiration_at' => '2026-05-08 12:00:00',
                'payment_limit_date' => '2026-05-09',
                'boleto_url' => 'https://example.test/boleto.pdf',
                'boleto_barcode' => '3419114400000001000109819643416091015649600',
                'boleto_digitable_line' => '34191098189643416091501564960001114400000001000',
                'client' => [
                    'first_name' => 'Reginaldo',
                    'last_name' => 'do Prado',
                    'document' => '09409616875',
                    'email' => 'reginaldo@example.test',
                ],
                'establishment' => [
                    'id' => '5001',
                    'name' => 'CELCOIN',
                ],
                'billing_instructions' => [],
                'fees_banking' => [
                    [
                        'id' => 99,
                        'fees' => [
                            'pix' => 999,
                        ],
                    ],
                ],
            ]);
        $boletoService->expects($this->once())
            ->method('normalizarResposta')
            ->willReturnArgument(0);

        $this->app->instance(BoletoService::class, $boletoService);

        $response = $this->actingAs($user)->getJson('/api/spa/cobranca/boleto/boleto-123');

        $response
            ->assertOk()
            ->assertJsonPath('boleto.fees_banking.0.id', 8)
            ->assertJsonPath('boleto.fees_banking.0.fees.pix', 100)
            ->assertJsonPath('boleto.establishment.name', 'CELCOIN');
    }

    public function test_cobranca_boleto_detail_infers_establishment_when_amounts_do_not_indicate_client_payment(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => '5001',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]);

        $boletoService = $this->createMock(BoletoService::class);
        $boletoService->expects($this->once())
            ->method('consultarBoleto')
            ->with('boleto-321')
            ->willReturn([
                '_id' => 'boleto-321',
                'status' => 'PENDING',
                'amount' => 1000,
                'original_amount' => 1000,
                'fees' => 0,
                'gateway_key' => 'CELCOIN',
                'authorization_code' => 'CELCOIN',
                'created_at' => '2026-05-04 13:43:00',
                'updated_at' => '2026-05-04 13:45:00',
                'expiration_at' => '2026-05-08 12:00:00',
                'payment_limit_date' => '2026-05-09',
                'boleto_url' => 'https://example.test/boleto.pdf',
                'boleto_barcode' => '3419114400000001000109819643416091015649600',
                'boleto_digitable_line' => '34191098189643416091501564960001114400000001000',
                'client' => [
                    'first_name' => 'Reginaldo',
                    'last_name' => 'do Prado',
                    'document' => '09409616875',
                    'email' => 'reginaldo@example.test',
                ],
                'establishment' => [
                    'id' => '5001',
                    'name' => 'CELCOIN',
                ],
                'billing_instructions' => [],
            ]);
        $boletoService->expects($this->once())
            ->method('normalizarResposta')
            ->willReturnArgument(0);

        $this->app->instance(BoletoService::class, $boletoService);

        $response = $this->actingAs($user)->getJson('/api/spa/cobranca/boleto/boleto-321');

        $response
            ->assertOk()
            ->assertJsonPath('boleto.juros', 'ESTABLISHMENT');
    }

    public function test_cobranca_boleto_detail_denies_access_for_other_establishment(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => '5001',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]);

        $boletoService = $this->createMock(BoletoService::class);
        $boletoService->expects($this->once())
            ->method('consultarBoleto')
            ->with('boleto-999')
            ->willReturn([
                '_id' => 'boleto-999',
                'status' => 'PENDING',
                'establishment' => ['id' => '9999', 'name' => 'Outro'],
            ]);
        $boletoService->expects($this->once())
            ->method('normalizarResposta')
            ->willReturnArgument(0);

        $this->app->instance(BoletoService::class, $boletoService);

        $response = $this->actingAs($user)->getJson('/api/spa/cobranca/boleto/boleto-999');

        $response->assertForbidden();
    }

    public function test_cobranca_boleto_delete_cancela_boleto_no_gateway(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => '5001',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]);

        $boletoService = $this->createMock(BoletoService::class);
        $boletoService->expects($this->once())
            ->method('consultarBoleto')
            ->with('boleto-123')
            ->willReturn([
                '_id' => 'boleto-123',
                'status' => 'PENDING',
                'establishment' => ['id' => '5001', 'name' => 'CELCOIN'],
            ]);
        $boletoService->expects($this->once())
            ->method('deletarBoleto')
            ->with('boleto-123')
            ->willReturn([
                '_id' => 'boleto-123',
                'status' => 'CANCELED',
            ]);

        $this->app->instance(BoletoService::class, $boletoService);

        $response = $this->actingAs($user)->deleteJson('/api/spa/cobranca/boleto/boleto-123');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Boleto cancelado com sucesso.');
    }

    public function test_cobranca_boleto_page_uses_the_shared_money_input_without_currency_symbol_for_fees_fields(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/cobranca/CobrancaBoletoPage.jsx'));

        $this->assertSame(3, substr_count($pageSource, 'showCurrencySymbol={false}'));
        $this->assertSame(3, substr_count($pageSource, 'MoneyInputField size="large" placeholder="0,00" showCurrencySymbol={false}'));
        $this->assertStringContainsString('const interestOptions = [', $pageSource);
        $this->assertStringContainsString('juros: \'ESTABLISHMENT\'', $pageSource);
        $this->assertStringContainsString('juros: values.juros ?? \'ESTABLISHMENT\'', $pageSource);
        $this->assertStringContainsString('label="Quem paga as taxas"', $pageSource);
        $this->assertStringContainsString('name="juros"', $pageSource);
        $this->assertStringContainsString('options={interestOptions}', $pageSource);
    }

    public function test_cobranca_boleto_detail_page_shows_who_pays_the_fees(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/cobranca/CobrancaBoletoDetailPage.jsx'));

        $this->assertStringContainsString('function formatTaxPayer(value)', $pageSource);
        $this->assertStringContainsString("case 'CLIENT':", $pageSource);
        $this->assertStringContainsString("case 'ESTABLISHMENT':", $pageSource);
        $this->assertStringContainsString('Quem paga as taxas', $pageSource);
        $this->assertStringContainsString('formatTaxPayer(boleto.juros)', $pageSource);
    }
}
