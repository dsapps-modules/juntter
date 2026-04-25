<?php

namespace Tests\Feature;

use App\Models\PaytimeEstablishment;
use App\Models\PaytimeTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaDashboardOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_overview_returns_summary_data(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);

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

        PaytimeTransaction::create([
            'external_id' => 'trx-1',
            'establishment_id' => '1001',
            'type' => 'PIX',
            'status' => 'PAID',
            'amount' => 35000,
            'original_amount' => 35000,
            'fees' => 0,
        ]);

        $response = $this->actingAs($user)->getJson('/api/spa/dashboard?mes=4&ano=2026');

        $response
            ->assertOk()
            ->assertJsonPath('period.month', 4)
            ->assertJsonPath('period.year', 2026)
            ->assertJsonPath('period.label', 'Abril 2026')
            ->assertJsonPath('overview_cards.0.label', 'Faturamento Líquido')
            ->assertJsonPath('overview_cards.5.value', 'Consultar Extrato')
            ->assertJsonPath('distribution_sections.0.label', 'Cartão de Crédito')
            ->assertJsonPath('distribution_sections.0.cards.0.kind', 'amount')
            ->assertJsonPath('status_sections.0.key', 'status_counts')
            ->assertJsonPath('status_sections.0.cards.0.label', 'Pagamento Efetivado')
            ->assertJsonPath('status_sections.1.key', 'status_percentages')
            ->assertJsonPath('status_sections.1.cards.0.kind', 'percent')
            ->assertJsonPath('summary.total_establishments', 1)
            ->assertJsonPath('summary.active_establishments', 1)
            ->assertJsonPath('summary.total_transactions', 1)
            ->assertJsonPath('rows.0.name', 'Acme Corp');
    }
}
