<?php

namespace Tests\Feature;

use App\Models\LinkPagamento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkPagamentoPixControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_a_pix_link_and_returns_the_detail_route_for_json_clients(): void
    {
        $user = $this->makeVendorUser('127700');

        $response = $this
            ->actingAs($user)
            ->postJson('/links-pagamento-pix', [
                'descricao' => 'Link de teste',
                'valor' => '5,55',
                'juros' => 'CLIENT',
                'data_expiracao' => now()->addDay()->format('Y-m-d'),
                'dados_cliente_preenchidos' => [
                    'nome' => 'Maria',
                    'sobrenome' => 'Silva',
                    'email' => 'maria@example.com',
                    'telefone' => '(11) 99999-9999',
                    'documento' => '123.456.789-09',
                ],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Link de pagamento PIX criado com sucesso!');

        $link = LinkPagamento::findOrFail($response->json('link_id'));

        $this->assertSame('127700', (string) $link->estabelecimento_id);
        $this->assertSame('PIX', $link->tipo_pagamento);
        $this->assertSame('ATIVO', $link->status);
        $this->assertSame('Link de teste', $link->descricao);
        $this->assertSame('/app/links-pagamento-pix/'.$link->id, $response->json('redirect'));
    }

    public function test_show_redirects_to_the_pix_detail_spa_route(): void
    {
        $user = $this->makeVendorUser('127700');

        $link = LinkPagamento::create([
            'estabelecimento_id' => '127700',
            'codigo_unico' => 'link_teste_01',
            'descricao' => 'Link de teste',
            'valor' => 5.55,
            'valor_centavos' => 555,
            'parcelas' => [1],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'PIX',
        ]);

        $response = $this->actingAs($user)->get('/links-pagamento-pix/'.$link->id);

        $response->assertRedirect('/app/links-pagamento-pix/'.$link->id);
    }

    public function test_destroy_returns_json_when_requested_for_api_clients(): void
    {
        $user = $this->makeVendorUser('127700');

        $link = LinkPagamento::create([
            'estabelecimento_id' => '127700',
            'codigo_unico' => 'link_teste_02',
            'descricao' => 'Link de teste',
            'valor' => 5.55,
            'valor_centavos' => 555,
            'parcelas' => [1],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'PIX',
        ]);

        $response = $this
            ->actingAs($user)
            ->deleteJson('/links-pagamento-pix/'.$link->id);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Link de pagamento PIX excluído com sucesso!');

        $this->assertDatabaseMissing('links_pagamento', [
            'id' => $link->id,
        ]);
    }

    private function makeVendorUser(string $establishmentId): User
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => $establishmentId,
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]);

        return $user;
    }
}
