<?php

namespace Tests\Feature;

use App\Models\PaytimeEstablishment;
use App\Models\PaytimeTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaVendedoresOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendedores_overview_returns_vendor_data(): void
    {
        $vendor = User::factory()->create([
            'name' => 'João Vendedor',
            'email' => 'joao@example.com',
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $vendor->vendedor()->create([
            'estabelecimento_id' => '9001',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'comissao' => 4.5,
            'meta_vendas' => 10000,
            'must_change_password' => true,
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
            'amount' => 20000,
            'original_amount' => 20000,
            'fees' => 0,
        ]);

        $response = $this->actingAs($vendor)->getJson('/api/spa/vendedores');

        $response
            ->assertOk()
            ->assertJsonPath('summary.total_vendors', 1)
            ->assertJsonPath('summary.active_vendors', 1)
            ->assertJsonPath('rows.0.name', 'João Vendedor')
            ->assertJsonPath('rows.0.establishment', 'Loja Teste');
    }
}
