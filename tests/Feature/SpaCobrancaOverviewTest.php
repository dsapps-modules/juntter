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
            'metadata' => [
                'request' => [
                    'descricao' => 'Cobrança do mês',
                ],
            ],
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
            ->assertJsonPath('rows.0.display_title', 'Cobrança do mês')
            ->assertJsonPath('rows.0.display_subtitle', '1')
            ->assertJsonPath('rows.0.pix_customer_fee_cents', 0)
            ->assertJsonPath('rows.0.pix_customer_fee', 'R$ 0,00')
            ->assertJsonPath('link_rows.0.display_title', 'Link Pix de teste')
            ->assertJsonPath('link_rows.0.display_subtitle', 'link_pix_01')
            ->assertJsonCount(1, 'link_rows');
    }

    public function test_cobranca_overview_uses_qr_code_label_when_pix_transaction_has_no_description(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);

        PaytimeTransaction::create([
            'external_id' => 'trx-no-description',
            'establishment_id' => '5001',
            'type' => 'PIX',
            'status' => 'PENDING',
            'amount' => 1800,
            'original_amount' => 1800,
            'fees' => 0,
            'pix_customer_fee_cents' => 180,
            'customer_name' => 'Cliente Sem Descrição',
            'metadata' => [
                'pix' => [
                    'transaction_id' => 'trx-no-description',
                    'pix_code' => '00020126580014br.gov.bcb.pix...',
                ],
            ],
        ]);

        $response = $this->actingAs($user)->getJson('/api/spa/cobranca');

        $response
            ->assertOk()
            ->assertJsonPath('rows.0.display_title', 'QR Code')
            ->assertJsonPath('rows.0.display_subtitle', '1')
            ->assertJsonPath('rows.0.pix_customer_fee_cents', 180)
            ->assertJsonPath('rows.0.pix_customer_fee', 'R$ 1,80');
    }

    public function test_cobranca_overview_returns_all_card_links_for_the_selected_month_regardless_of_status(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);

        $linkCardOld = LinkPagamento::create([
            'estabelecimento_id' => '5001',
            'codigo_unico' => 'link_card_old',
            'descricao' => 'TV Antiga',
            'valor' => 950.00,
            'valor_centavos' => 95000,
            'parcelas' => [1, 2, 3],
            'juros' => 'CLIENT',
            'status' => 'EXPIRADO',
            'tipo_pagamento' => 'CARTAO',
        ]);
        $linkCardOld->forceFill(['created_at' => now()->setDate(2026, 8, 1)->setTime(10, 0)])->saveQuietly();

        $linkCardMid = LinkPagamento::create([
            'estabelecimento_id' => '5001',
            'codigo_unico' => 'link_card_mid',
            'descricao' => 'Teste: link de pagamento',
            'valor' => 103.00,
            'valor_centavos' => 10300,
            'parcelas' => [1],
            'juros' => 'CLIENT',
            'status' => 'INATIVO',
            'tipo_pagamento' => 'CARTAO',
        ]);
        $linkCardMid->forceFill(['created_at' => now()->setDate(2026, 8, 2)->setTime(10, 0)])->saveQuietly();

        $linkCardNew = LinkPagamento::create([
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
        $linkCardNew->forceFill(['created_at' => now()->setDate(2026, 8, 3)->setTime(10, 0)])->saveQuietly();

        $linkCardPending = LinkPagamento::create([
            'estabelecimento_id' => '5001',
            'codigo_unico' => 'link_card_pending',
            'descricao' => 'Link pendente de teste',
            'valor' => 120.00,
            'valor_centavos' => 12000,
            'parcelas' => [1],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'CARTAO',
        ]);
        $linkCardPending->forceFill(['created_at' => now()->setDate(2026, 8, 4)->setTime(10, 0)])->saveQuietly();

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

        PaytimeTransaction::create([
            'external_id' => 'card-link-new-transaction',
            'establishment_id' => '5001',
            'type' => 'CREDIT',
            'status' => 'PAID',
            'amount' => 150000,
            'original_amount' => 150000,
            'fees' => 0,
            'customer_name' => 'Cliente Novo',
            'metadata' => [
                'data' => [
                    'info_additional' => [
                        ['key' => 'link_pagamento_id', 'value' => (string) $linkCardNew->id],
                        ['key' => 'codigo_unico', 'value' => 'link_card_new'],
                    ],
                ],
            ],
        ]);

        PaytimeTransaction::create([
            'external_id' => 'card-link-mid-transaction',
            'establishment_id' => '5001',
            'type' => 'CREDIT',
            'status' => 'PENDING',
            'amount' => 10300,
            'original_amount' => 10300,
            'fees' => 0,
            'customer_name' => 'Cliente Meio',
            'metadata' => [
                'data' => [
                    'info_additional' => [
                        ['key' => 'link_pagamento_id', 'value' => (string) $linkCardMid->id],
                        ['key' => 'codigo_unico', 'value' => 'link_card_mid'],
                    ],
                ],
            ],
        ]);

        PaytimeTransaction::create([
            'external_id' => 'card-link-old-transaction',
            'establishment_id' => '5001',
            'type' => 'CREDIT',
            'status' => 'CANCELED',
            'amount' => 95000,
            'original_amount' => 95000,
            'fees' => 0,
            'customer_name' => 'Cliente Antigo',
            'metadata' => [
                'data' => [
                    'info_additional' => [
                        ['key' => 'link_pagamento_id', 'value' => (string) $linkCardOld->id],
                        ['key' => 'codigo_unico', 'value' => 'link_card_old'],
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($user)->getJson('/api/spa/cobranca');

        $response
            ->assertOk()
            ->assertJsonCount(4, 'card_link_rows')
            ->assertJsonPath('card_link_rows.0.title', 'Link pendente de teste')
            ->assertJsonPath('card_link_rows.1.title', 'TV 55 Polegadas')
            ->assertJsonPath('card_link_rows.2.title', 'Teste: link de pagamento')
            ->assertJsonPath('card_link_rows.3.title', 'TV Antiga')
            ->assertJsonPath('card_link_rows.0.active', true)
            ->assertJsonPath('card_link_rows.1.active', true)
            ->assertJsonPath('card_link_rows.2.active', false)
            ->assertJsonPath('card_link_rows.3.active', false)
            ->assertJsonPath('card_link_rows.0.status', 'PENDING')
            ->assertJsonPath('card_link_rows.1.status', 'PAID')
            ->assertJsonPath('card_link_rows.2.status', 'PENDING')
            ->assertJsonPath('card_link_rows.3.status', 'CANCELED')
            ->assertJsonPath('card_link_rows.0.detail_href', '/links-pagamento/'.$linkCardPending->id)
            ->assertJsonPath('card_link_rows.1.detail_href', '/links-pagamento/'.$linkCardNew->id)
            ->assertJsonPath('card_link_rows.2.detail_href', '/links-pagamento/'.$linkCardMid->id)
            ->assertJsonPath('card_link_rows.3.detail_href', '/links-pagamento/'.$linkCardOld->id)
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

    public function test_cobranca_overview_exposes_pix_qr_data_for_pending_transactions(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);

        PaytimeTransaction::create([
            'external_id' => 'trx-pix-pending',
            'establishment_id' => '5001',
            'type' => 'PIX',
            'status' => 'PENDING',
            'amount' => 12550,
            'original_amount' => 12550,
            'fees' => 0,
            'customer_name' => 'Maria Silva',
            'customer_document' => '123.456.789-09',
            'metadata' => [
                'pix' => [
                    'transaction_id' => 'trx-pix-pending',
                    'pix_code' => '00020126580014br.gov.bcb.pix...',
                    'qr_code' => [
                        'qrcode' => 'data:image/png;base64,ZmFrZQ==',
                        'emv' => '00020126580014br.gov.bcb.pix...',
                    ],
                ],
                'request' => [
                    'descricao' => 'QR Code do mês',
                ],
            ],
            'created_at' => Carbon::now()->startOfMonth()->addDays(2)->setTime(14, 30),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/spa/cobranca?period='.Carbon::now()->format('Y-m'));

        $response
            ->assertOk()
            ->assertJsonPath('rows.0.display_title', 'QR Code do mês')
            ->assertJsonPath('rows.0.display_subtitle', '1')
            ->assertJsonPath('rows.0.status', 'Pendente')
            ->assertJsonPath('rows.0.pix_code', '00020126580014br.gov.bcb.pix...')
            ->assertJsonPath('rows.0.qr_code.qrcode', 'data:image/png;base64,ZmFrZQ==');
    }
}
