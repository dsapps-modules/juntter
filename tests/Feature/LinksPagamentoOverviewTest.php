<?php

namespace Tests\Feature;

use App\Models\LinkPagamento;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinksPagamentoOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_links_pagamento_overview_returns_monthly_links_for_all_payment_types(): void
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

        $now = Carbon::now();

        LinkPagamento::forceCreate([
            'estabelecimento_id' => '5001',
            'codigo_unico' => 'link_card_new',
            'descricao' => 'Cartão recente',
            'valor' => 150.00,
            'valor_centavos' => 15000,
            'parcelas' => [1, 2, 3],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'CARTAO',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        LinkPagamento::forceCreate([
            'estabelecimento_id' => '5001',
            'codigo_unico' => 'link_boleto_new',
            'descricao' => 'Boleto recente',
            'valor' => 200.00,
            'valor_centavos' => 20000,
            'parcelas' => [1],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'BOLETO',
            'created_at' => $now->copy()->subMinute(),
            'updated_at' => $now->copy()->subMinute(),
        ]);

        LinkPagamento::forceCreate([
            'estabelecimento_id' => '5001',
            'codigo_unico' => 'link_pix_recent',
            'descricao' => 'Pix recente',
            'valor' => 105.00,
            'valor_centavos' => 10500,
            'parcelas' => [1],
            'juros' => 'CLIENT',
            'status' => 'PAID',
            'tipo_pagamento' => 'PIX',
            'created_at' => $now->copy()->subMinutes(2),
            'updated_at' => $now->copy()->subMinutes(2),
        ]);

        LinkPagamento::forceCreate([
            'estabelecimento_id' => '5001',
            'codigo_unico' => 'link_pix_old',
            'descricao' => 'Pix fora do mês atual',
            'valor' => 100.00,
            'valor_centavos' => 10000,
            'parcelas' => [1],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'PIX',
            'created_at' => $now->copy()->subYear(),
            'updated_at' => $now->copy()->subYear(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/spa/links-pagamento?period='.$now->format('Y-m'));

        $response
            ->assertOk()
            ->assertJsonPath('selected_period', $now->format('Y-m'))
            ->assertJsonPath('summary.total_links', 3)
            ->assertJsonPath('summary.card_links', 1)
            ->assertJsonPath('summary.pix_links', 1)
            ->assertJsonPath('summary.boleto_links', 1)
            ->assertJsonPath('summary.paid_links', 1)
            ->assertJsonPath('rows.0.code', 'link_card_new')
            ->assertJsonPath('rows.1.code', 'link_boleto_new')
            ->assertJsonPath('rows.2.code', 'link_pix_recent')
            ->assertJsonPath('recent_links.0.raw_type', 'CARTAO')
            ->assertJsonPath('recent_links.1.raw_type', 'BOLETO')
            ->assertJsonPath('recent_links.2.raw_type', 'PIX');
    }
}
