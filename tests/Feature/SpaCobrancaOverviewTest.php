<?php

namespace Tests\Feature;

use App\Models\LinkPagamento;
use App\Models\PaytimeTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaCobrancaOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_cobranca_overview_returns_transaction_and_link_data(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);

        PaytimeTransaction::create([
            'external_id' => 'trx-100',
            'establishment_id' => '5001',
            'type' => 'CREDIT',
            'status' => 'PAID',
            'amount' => 12500,
            'original_amount' => 12500,
            'fees' => 500,
            'customer_name' => 'Cliente Teste',
        ]);

        LinkPagamento::create([
            'estabelecimento_id' => '5001',
            'codigo_unico' => 'link_teste_01',
            'descricao' => 'Link de teste',
            'valor' => 125.00,
            'valor_centavos' => 12500,
            'parcelas' => [1, 2, 3],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'CARTAO',
        ]);

        $response = $this->actingAs($user)->getJson('/api/spa/cobranca');

        $response
            ->assertOk()
            ->assertJsonPath('summary.total_transactions', 1)
            ->assertJsonPath('summary.paid_transactions', 1)
            ->assertJsonPath('summary.active_links', 1)
            ->assertJsonPath('rows.0.customer', 'Cliente Teste');
    }
}
