<?php

namespace Tests\Feature;

use App\Models\LinkPagamento;
use App\Models\PaytimeTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaCobrancaOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_cobranca_overview_returns_transaction_and_link_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
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
            ->assertJsonPath('seller_name', 'Test User')
            ->assertJsonPath('summary.total_transactions', 1)
            ->assertJsonPath('summary.paid_transactions', 1)
            ->assertJsonPath('summary.active_links', 1)
            ->assertJsonPath('rows.0.customer', 'Cliente Teste')
            ->assertJsonPath('link_rows.0.title', 'Link de teste');
    }

    public function test_cobranca_overview_filters_transactions_by_selected_period(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);

        $currentPeriod = Carbon::now()->format('Y-m');
        $previousPeriod = Carbon::now()->subMonthNoOverflow()->format('Y-m');

        PaytimeTransaction::create([
            'external_id' => 'trx-current',
            'establishment_id' => '5001',
            'type' => 'CREDIT',
            'status' => 'PAID',
            'amount' => 12500,
            'original_amount' => 12500,
            'fees' => 500,
            'customer_name' => 'Cliente Atual',
            'created_at' => Carbon::now()->startOfMonth()->addDays(9)->setTime(10, 0),
            'updated_at' => now(),
        ]);

        PaytimeTransaction::create([
            'external_id' => 'trx-previous',
            'establishment_id' => '5001',
            'type' => 'PIX',
            'status' => 'PENDING',
            'amount' => 9900,
            'original_amount' => 9900,
            'fees' => 250,
            'customer_name' => 'Cliente Antigo',
            'created_at' => Carbon::now()->subMonthNoOverflow()->startOfMonth()->addDays(14)->setTime(9, 0),
            'updated_at' => now(),
        ]);

        $currentResponse = $this->actingAs($user)->getJson('/api/spa/cobranca?period='.$currentPeriod);

        $currentResponse
            ->assertOk()
            ->assertJsonPath('selected_period', $currentPeriod)
            ->assertJsonPath('summary.total_transactions', 1)
            ->assertJsonPath('rows.0.customer', 'Cliente Atual')
            ->assertJsonPath('periods.0.value', 'all')
            ->assertJsonPath('periods.1.value', $currentPeriod);

        $allResponse = $this->actingAs($user)->getJson('/api/spa/cobranca?period=all');

        $allResponse
            ->assertOk()
            ->assertJsonPath('selected_period', 'all')
            ->assertJsonPath('summary.total_transactions', 2)
            ->assertJsonPath('rows.0.customer', 'Cliente Atual')
            ->assertJsonPath('rows.1.customer', 'Cliente Antigo')
            ->assertJsonPath('periods.0.value', 'all');
    }

    public function test_cobranca_overview_includes_pix_transactions_for_current_period(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);

        PaytimeTransaction::create([
            'external_id' => 'trx-pix',
            'establishment_id' => '5001',
            'type' => 'PIX',
            'status' => 'PENDING',
            'amount' => 9900,
            'original_amount' => 9900,
            'fees' => 250,
            'customer_name' => 'Cliente Pix',
            'created_at' => Carbon::now()->startOfMonth()->addDays(2)->setTime(14, 30),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/spa/cobranca?period='.Carbon::now()->format('Y-m'));

        $response
            ->assertOk()
            ->assertJsonPath('summary.pix_transactions', 1)
            ->assertJsonPath('rows.0.type', 'PIX')
            ->assertJsonPath('rows.0.customer', 'Cliente Pix');
    }
}
