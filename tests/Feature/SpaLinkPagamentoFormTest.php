<?php

namespace Tests\Feature;

use App\Models\LinkPagamento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaLinkPagamentoFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_link_can_be_created_from_the_spa(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => '7001',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($user)->postJson('/links-pagamento', [
            'descricao' => 'Link do teste',
            'valor' => 'R$ 25,00',
            'parcelas' => 3,
            'juros' => 'CLIENT',
            'data_expiracao' => now()->addDay()->format('Y-m-d'),
            'url_retorno' => 'https://example.com/obrigado',
            'url_webhook' => 'https://example.com/webhook',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('redirect', '/app/links-pagamento');

        $this->assertDatabaseHas('links_pagamento', [
            'estabelecimento_id' => '7001',
            'descricao' => 'Link do teste',
        ]);
    }

    public function test_card_link_can_be_updated_from_the_spa(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => '7002',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]);

        $link = LinkPagamento::create([
            'estabelecimento_id' => '7002',
            'codigo_unico' => 'link_teste_02',
            'descricao' => 'Link antigo',
            'valor' => 25.00,
            'valor_centavos' => 2500,
            'parcelas' => [1, 2, 3],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'CARTAO',
        ]);

        $response = $this->actingAs($user)->putJson('/links-pagamento/'.$link->id, [
            'descricao' => 'Link atualizado',
            'valor' => 'R$ 30,00',
            'parcelas' => 4,
            'juros' => 'ESTABLISHMENT',
            'data_expiracao' => now()->addDays(2)->format('Y-m-d'),
            'url_retorno' => 'https://example.com/obrigado',
            'url_webhook' => 'https://example.com/webhook',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('redirect', '/app/links-pagamento');

        $this->assertDatabaseHas('links_pagamento', [
            'id' => $link->id,
            'descricao' => 'Link atualizado',
        ]);
    }
}
