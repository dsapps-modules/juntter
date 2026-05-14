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

    public function test_links_pagamento_overview_returns_links_in_descending_order_for_pix_filtering(): void
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

        LinkPagamento::create([
            'estabelecimento_id' => '5001',
            'codigo_unico' => 'link_card_new',
            'descricao' => 'Cartão recente',
            'valor' => 150.00,
            'valor_centavos' => 15000,
            'parcelas' => [1, 2, 3],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'CARTAO',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        LinkPagamento::create([
            'estabelecimento_id' => '5001',
            'codigo_unico' => 'link_pix_recent',
            'descricao' => 'Pix criado com a finalidade de testar a geração de Link de pagamento no checkout Juntter.',
            'valor' => 105.00,
            'valor_centavos' => 10500,
            'parcelas' => [1],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'PIX',
            'created_at' => Carbon::now()->subMinute(),
            'updated_at' => Carbon::now()->subMinute(),
        ]);

        LinkPagamento::create([
            'estabelecimento_id' => '5001',
            'codigo_unico' => 'link_pix_old',
            'descricao' => 'Energia Elétrica - Residência',
            'valor' => 100.00,
            'valor_centavos' => 10000,
            'parcelas' => [1],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'PIX',
            'created_at' => Carbon::now()->subMinutes(2),
            'updated_at' => Carbon::now()->subMinutes(2),
        ]);

        $response = $this->actingAs($user)->getJson('/api/spa/links-pagamento');

        $response
            ->assertOk()
            ->assertJsonCount(3, 'recent_links')
            ->assertJsonPath('recent_links.0.raw_type', 'CARTAO')
            ->assertJsonPath('recent_links.1.raw_type', 'PIX')
            ->assertJsonPath('recent_links.2.raw_type', 'PIX');
    }
}
