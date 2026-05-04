<?php

namespace Tests\Feature;

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
            ->assertJsonPath('boleto.customer.first_name', 'Reginaldo')
            ->assertJsonPath('boleto.establishment.name', 'CELCOIN')
            ->assertJsonPath('boleto.billing_instructions.0.name', 'late_fee');
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
}
