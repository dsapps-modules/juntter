<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BalanceService;
use App\Services\TransacaoService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaCobrancaSaldoExtratoTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_saldo_extrato_overview_returns_balance_and_extract_rows(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 15, 12, 0, 0));

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

        $balanceService = $this->createMock(BalanceService::class);
        $balanceService->expects($this->once())
            ->method('saldoAtual')
            ->with($this->callback(function (array $filters): bool {
                $periodFilters = json_decode((string) ($filters['filters'] ?? ''), true);

                return ($filters['extra_headers']['establishment_id'] ?? null) === '5001'
                    && ($periodFilters['created_at']['min'] ?? null) === '2026-05-01'
                    && ($periodFilters['created_at']['max'] ?? null) === '2026-05-31';
            }))
            ->willReturn([
                'balance' => 125000,
                'blocked_balance' => 4500,
                'total_balance' => 129500,
            ]);

        $transacaoService = $this->createMock(TransacaoService::class);
        $transacaoService->expects($this->once())
            ->method('consultarExtratoEstabelecimento')
            ->with($this->callback(function (array $filters): bool {
                $periodFilters = json_decode((string) ($filters['filters'] ?? ''), true);

                return ($filters['extra_headers']['establishment_id'] ?? null) === '5001'
                    && ($periodFilters['created_at']['min'] ?? null) === '2026-05-01'
                    && ($periodFilters['created_at']['max'] ?? null) === '2026-05-31';
            }))
            ->willReturn([
                'data' => [
                    [
                        '_id' => 'mov-1',
                        'type' => 'PIX',
                        'modality' => 'IN',
                        'description' => 'Recebimento PIX',
                        'status' => 'COMPLETED',
                        'amount' => 8000,
                        'created_at' => '2026-05-15 10:30:00',
                        'additionalInformation' => [
                            'old_balance' => 117000,
                            'current_balance' => 125000,
                        ],
                    ],
                    [
                        '_id' => 'mov-2',
                        'type' => 'FEES',
                        'modality' => 'OUT',
                        'description' => 'Tarifa',
                        'status' => 'PAID',
                        'amount' => 500,
                        'created_at' => '2026-05-14 08:20:00',
                        'additionalInformation' => [
                            'old_balance' => 117500,
                            'current_balance' => 117000,
                        ],
                    ],
                ],
            ]);

        $this->app->instance(BalanceService::class, $balanceService);
        $this->app->instance(TransacaoService::class, $transacaoService);

        $response = $this->actingAs($user)->getJson('/api/spa/cobranca/saldoextrato');

        $response
            ->assertOk()
            ->assertJsonPath('seller_name', $user->name)
            ->assertJsonPath('establishment.id', '5001')
            ->assertJsonPath('balance.available_label', 'R$ 1.250,00')
            ->assertJsonPath('balance.blocked_label', 'R$ 45,00')
            ->assertJsonPath('balance.total_label', 'R$ 1.295,00')
            ->assertJsonPath('summary.movements', 2)
            ->assertJsonPath('summary.incoming_total_label', 'R$ 80,00')
            ->assertJsonPath('summary.outgoing_total_label', 'R$ 5,00')
            ->assertJsonPath('selected_period', '2026-05')
            ->assertJsonPath('rows.0.id', 'mov-1')
            ->assertJsonPath('rows.0.type_label', 'PIX')
            ->assertJsonPath('rows.0.modality_label', 'Entrada')
            ->assertJsonPath('rows.0.amount_signed_label', '+R$ 80,00')
            ->assertJsonPath('rows.1.id', 'mov-2')
            ->assertJsonPath('rows.1.type_label', 'Tarifa')
            ->assertJsonPath('rows.1.modality_label', 'Saída')
            ->assertJsonPath('rows.1.amount_signed_label', '-R$ 5,00');
    }

    public function test_saldo_extrato_overview_returns_empty_state_when_the_user_has_no_establishment(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 15, 12, 0, 0));

        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/spa/cobranca/saldoextrato');

        $response
            ->assertOk()
            ->assertJsonPath('establishment', null)
            ->assertJsonPath('balance.available_label', 'R$ 0,00')
            ->assertJsonPath('summary.movements', 0)
            ->assertJsonPath('selected_period', '2026-05')
            ->assertJsonPath('rows', []);
    }
}
