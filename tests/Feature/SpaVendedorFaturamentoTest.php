<?php

namespace Tests\Feature;

use App\Models\PaytimeEstablishment;
use App\Models\PaytimeTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaVendedorFaturamentoTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendedor_faturamento_overview_returns_ranking_and_summary(): void
    {
        $admin = User::factory()->create([
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);

        PaytimeEstablishment::create([
            'id' => 9001,
            'fantasy_name' => 'Loja Teste',
            'email' => 'loja@example.com',
            'active' => true,
            'status' => 'APPROVED',
            'revenue' => 4520.20,
        ]);

        PaytimeTransaction::create([
            'external_id' => 'trx-v1',
            'establishment_id' => '9001',
            'type' => 'PIX',
            'status' => 'PAID',
            'amount' => 9500,
            'original_amount' => 10000,
            'fees' => 500,
        ]);

        PaytimeTransaction::create([
            'external_id' => 'trx-v2',
            'establishment_id' => '9001',
            'type' => 'PIX',
            'status' => 'PAID',
            'amount' => 19500,
            'original_amount' => 20000,
            'fees' => 500,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/spa/vendedores/faturamento?mes=4&ano=2026');

        $response
            ->assertOk()
            ->assertJsonPath('summary.total_registros', 1)
            ->assertJsonPath('summary.transacoes', 2)
            ->assertJsonPath('summary.total_bruto', 30000)
            ->assertJsonPath('summary.total_liquido', 29000)
            ->assertJsonPath('rows.0.nome', 'Loja Teste');
    }
}
