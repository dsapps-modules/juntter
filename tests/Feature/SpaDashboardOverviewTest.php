<?php

namespace Tests\Feature;

use App\Models\PaytimeEstablishment;
use App\Models\PaytimeTransaction;
use App\Models\User;
use App\Services\BalanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaDashboardOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_overview_returns_summary_data(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);
        $periodDate = Carbon::create(2026, 4, 15, 12, 0, 0);

        PaytimeEstablishment::create([
            'id' => 1001,
            'fantasy_name' => 'Acme Corp',
            'email' => 'acme@example.com',
            'active' => true,
            'status' => 'APPROVED',
            'revenue' => 1520.50,
            'address_json' => ['city' => 'São Paulo'],
            'responsible_json' => ['name' => 'Ana Souza'],
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => '1001',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]);

        PaytimeTransaction::create([
            'external_id' => 'trx-1',
            'establishment_id' => '1001',
            'type' => 'PIX',
            'status' => 'PAID',
            'amount' => 35000,
            'original_amount' => 35000,
            'fees' => 0,
            'created_at' => $periodDate,
            'updated_at' => $periodDate,
        ]);

        PaytimeTransaction::create([
            'external_id' => 'trx-2',
            'establishment_id' => '1001',
            'type' => 'PIX',
            'status' => 'PENDING',
            'amount' => 12000,
            'original_amount' => 12000,
            'fees' => 0,
            'created_at' => $periodDate,
            'updated_at' => $periodDate,
        ]);

        $balanceService = $this->createMock(BalanceService::class);
        $balanceService->expects($this->once())
            ->method('saldoAtual')
            ->with($this->callback(function (array $filters): bool {
                return ($filters['extra_headers']['establishment_id'] ?? null) === '1001';
            }))
            ->willReturn([
                'balance' => 12500,
            ]);

        $this->app->instance(BalanceService::class, $balanceService);

        $response = $this->actingAs($user)->getJson('/api/spa/dashboard?mes=4&ano=2026');

        $response
            ->assertOk()
            ->assertJsonPath('period.month', 4)
            ->assertJsonPath('period.year', 2026)
            ->assertJsonPath('period.label', 'Abril 2026')
            ->assertJsonPath('overview_cards.0.label', 'Faturamento Líquido')
            ->assertJsonPath('overview_cards.5.value', 'R$ 125,00')
            ->assertJsonPath('overview_cards.5.href', '/cobranca/saldoextrato')
            ->assertJsonPath('distribution_sections.0.label', 'Cartão de Crédito')
            ->assertJsonPath('distribution_sections.0.cards.0.kind', 'amount')
            ->assertJsonPath('status_sections.0.key', 'status_counts')
            ->assertJsonPath('status_sections.0.cards.0.label', 'Pagamento Efetivado')
            ->assertJsonPath('status_sections.1.key', 'status_percentages')
            ->assertJsonPath('status_sections.1.cards.0.kind', 'percent')
            ->assertJsonPath('summary.total_establishments', 1)
            ->assertJsonPath('summary.active_establishments', 1)
            ->assertJsonPath('summary.total_transactions', 1)
            ->assertJsonPath('summary.pending_transactions', 1)
            ->assertJsonPath('rows.0.name', 'Acme Corp');
    }

    public function test_dashboard_overview_counts_transactions_outside_top_rows_for_admin(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);
        $periodDate = Carbon::create(2026, 4, 15, 12, 0, 0);

        foreach (range(1, 12) as $index) {
            PaytimeEstablishment::create([
                'id' => 3000 + $index,
                'fantasy_name' => "Loja {$index}",
                'email' => "loja{$index}@example.com",
                'active' => true,
                'status' => 'APPROVED',
                'revenue' => 0,
                'address_json' => ['city' => 'São Paulo'],
                'responsible_json' => ['name' => "Responsável {$index}"],
            ]);
        }

        PaytimeEstablishment::create([
            'id' => 4000,
            'fantasy_name' => 'Loja Fora da Lista',
            'email' => 'fora@example.com',
            'active' => true,
            'status' => 'APPROVED',
            'revenue' => 0,
            'address_json' => ['city' => 'São Paulo'],
            'responsible_json' => ['name' => 'Responsável fora'],
        ]);

        PaytimeEstablishment::query()
            ->whereKey(4000)
            ->update(['updated_at' => now()->subDays(30)]);

        PaytimeTransaction::create([
            'external_id' => 'trx-outside',
            'establishment_id' => '4000',
            'type' => 'PIX',
            'status' => 'PAID',
            'amount' => 10457200,
            'original_amount' => 10457200,
            'fees' => 0,
            'created_at' => $periodDate,
            'updated_at' => $periodDate,
        ]);

        $response = $this->actingAs($user)->getJson('/api/spa/dashboard?mes=4&ano=2026');

        $response
            ->assertOk()
            ->assertJsonCount(12, 'rows')
            ->assertJsonPath('summary.total_establishments', 13)
            ->assertJsonPath('summary.active_establishments', 13)
            ->assertJsonPath('summary.total_transactions', 1);
    }
}
