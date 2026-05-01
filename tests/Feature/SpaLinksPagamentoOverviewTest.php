<?php

namespace Tests\Feature;

use App\Models\LinkPagamento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaLinksPagamentoOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_links_pagamento_overview_returns_link_data(): void
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
            'codigo_unico' => 'link_teste_01',
            'descricao' => 'Link de teste',
            'valor' => 125.00,
            'valor_centavos' => 12500,
            'parcelas' => [1, 2, 3],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'CARTAO',
        ]);

        $response = $this->actingAs($user)->getJson('/api/spa/links-pagamento');

        $response
            ->assertOk()
            ->assertJsonPath('summary.total_links', 1)
            ->assertJsonPath('summary.active_links', 1)
            ->assertJsonPath('rows.0.title', 'Link de teste');
    }

    public function test_link_pagamento_detail_returns_complete_link_data(): void
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

        $link = LinkPagamento::create([
            'estabelecimento_id' => '5001',
            'codigo_unico' => 'link_teste_02',
            'descricao' => 'Link de detalhe',
            'valor' => 125.00,
            'valor_centavos' => 12500,
            'parcelas' => [1],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'PIX',
        ]);

        $response = $this->actingAs($user)->getJson('/api/spa/links-pagamento/'.$link->id);

        $response
            ->assertOk()
            ->assertJsonPath('link.id', $link->id)
            ->assertJsonPath('link.codigo_unico', 'link_teste_02')
            ->assertJsonPath('link.url_completa', route('pagamento.link', 'link_teste_02'));
    }
}
