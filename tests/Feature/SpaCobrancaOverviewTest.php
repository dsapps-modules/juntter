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

    public function test_cobranca_overview_returns_transaction_and_pix_link_data(): void
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
            'codigo_unico' => 'link_pix_01',
            'descricao' => 'Link Pix de teste',
            'valor' => 125.00,
            'valor_centavos' => 12500,
            'parcelas' => [1, 2, 3],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'PIX',
        ]);

        LinkPagamento::create([
            'estabelecimento_id' => '5001',
            'codigo_unico' => 'link_cartao_01',
            'descricao' => 'Link cartao de teste',
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
            ->assertJsonPath('link_rows.0.title', 'Link Pix de teste')
            ->assertJsonCount(1, 'link_rows');
    }

    public function test_cobranca_overview_returns_the_two_most_recent_card_links(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);

        LinkPagamento::create([
            'estabelecimento_id' => '5001',
            'codigo_unico' => 'link_card_old',
            'descricao' => 'TV Antiga',
            'valor' => 950.00,
            'valor_centavos' => 95000,
            'parcelas' => [1, 2, 3],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'CARTAO',
        ]);

        LinkPagamento::create([
            'estabelecimento_id' => '5001',
            'codigo_unico' => 'link_card_mid',
            'descricao' => 'Teste: link de pagamento',
            'valor' => 103.00,
            'valor_centavos' => 10300,
            'parcelas' => [1],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'CARTAO',
        ]);

        LinkPagamento::create([
            'estabelecimento_id' => '5001',
            'codigo_unico' => 'link_card_new',
            'descricao' => 'TV 55 Polegadas',
            'valor' => 1500.00,
            'valor_centavos' => 150000,
            'parcelas' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'CARTAO',
        ]);

        LinkPagamento::create([
            'estabelecimento_id' => '5001',
            'codigo_unico' => 'link_pix_other',
            'descricao' => 'Pix separado',
            'valor' => 75.00,
            'valor_centavos' => 7500,
            'parcelas' => [1],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'PIX',
        ]);

        LinkPagamento::query()->where('codigo_unico', 'link_card_old')->update([
            'created_at' => Carbon::now()->subDays(2),
            'updated_at' => Carbon::now()->subDays(2),
        ]);

        LinkPagamento::query()->where('codigo_unico', 'link_card_mid')->update([
            'created_at' => Carbon::now()->subDay(),
            'updated_at' => Carbon::now()->subDay(),
        ]);

        LinkPagamento::query()->where('codigo_unico', 'link_card_new')->update([
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/spa/cobranca');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'recent_card_links')
            ->assertJsonPath('recent_card_links.0.title', 'TV 55 Polegadas')
            ->assertJsonPath('recent_card_links.1.title', 'Teste: link de pagamento')
            ->assertJsonPath('link_rows.0.title', 'Pix separado')
            ->assertJsonCount(1, 'link_rows');
    }

    public function test_cobranca_overview_returns_the_most_recent_pix_links_without_period_filtering(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);

        LinkPagamento::create([
            'estabelecimento_id' => '5001',
            'codigo_unico' => 'pix_old',
            'descricao' => 'Pix antigo',
            'valor' => 50.00,
            'valor_centavos' => 5000,
            'parcelas' => [1],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'PIX',
        ]);

        LinkPagamento::create([
            'estabelecimento_id' => '5001',
            'codigo_unico' => 'pix_recent',
            'descricao' => 'Pix recente',
            'valor' => 75.00,
            'valor_centavos' => 7500,
            'parcelas' => [1],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'PIX',
        ]);

        LinkPagamento::create([
            'estabelecimento_id' => '5001',
            'codigo_unico' => 'card_recent',
            'descricao' => 'Cartão recente',
            'valor' => 100.00,
            'valor_centavos' => 10000,
            'parcelas' => [1],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'CARTAO',
        ]);

        LinkPagamento::query()->where('codigo_unico', 'pix_old')->update([
            'created_at' => Carbon::now()->subMonths(2),
            'updated_at' => Carbon::now()->subMonths(2),
        ]);

        LinkPagamento::query()->where('codigo_unico', 'pix_recent')->update([
            'created_at' => Carbon::now()->subDay(),
            'updated_at' => Carbon::now()->subDay(),
        ]);

        LinkPagamento::query()->where('codigo_unico', 'card_recent')->update([
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/spa/cobranca?period=all');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'recent_pix_links')
            ->assertJsonPath('recent_pix_links.0.title', 'Pix recente')
            ->assertJsonPath('recent_pix_links.1.title', 'Pix antigo')
            ->assertJsonPath('recent_pix_links.0.type', 'PIX')
            ->assertJsonPath('recent_pix_links.1.type', 'PIX');
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
