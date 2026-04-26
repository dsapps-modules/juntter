<?php

namespace Tests\Feature;

use App\Models\LinkPagamento;
use App\Models\PaytimeEstablishment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_spa_shell_is_available_at_the_app_route(): void
    {
        $response = $this->get('/app');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_nested_spa_routes_return_the_same_shell(): void
    {
        $response = $this->get('/app/painel');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_home_route_is_available(): void
    {
        $response = $this->get('/app/home');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_login_route_is_available(): void
    {
        $response = $this->get('/app/login');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_password_recovery_route_is_available(): void
    {
        $response = $this->get('/app/forgot-password');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_password_reset_route_is_available(): void
    {
        $response = $this->get('/app/reset-password/example-token');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_email_verification_route_is_available(): void
    {
        $response = $this->get('/app/verify-email');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_change_password_route_is_available(): void
    {
        $response = $this->get('/app/change-password');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_profile_route_is_available(): void
    {
        $response = $this->get('/app/perfil');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_cobranca_route_is_available(): void
    {
        $response = $this->get('/app/cobranca');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_links_pagamento_route_is_available(): void
    {
        $response = $this->get('/app/links-pagamento');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_vendedores_route_is_available(): void
    {
        $response = $this->get('/app/vendedores');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_establishment_details_route_is_available(): void
    {
        $response = $this->get('/app/estabelecimentos/1');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_vendedores_access_route_is_available(): void
    {
        $response = $this->get('/app/vendedores/acesso');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_vendedores_faturamento_route_is_available(): void
    {
        $response = $this->get('/app/vendedores/faturamento');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_legacy_vendedores_routes_redirect_to_the_spa(): void
    {
        $admin = User::factory()->create([
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin);

        $this->get('/vendedores/acesso')->assertRedirect('/app/vendedores/acesso');
        $this->get('/vendedores/faturamento')->assertRedirect('/app/vendedores/faturamento');
    }

    public function test_legacy_links_pagamento_routes_redirect_to_the_spa(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => '9001',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
        ]);

        $link = LinkPagamento::create([
            'estabelecimento_id' => '9001',
            'codigo_unico' => 'legacy-link',
            'descricao' => 'Link legado',
            'valor' => 100.00,
            'valor_centavos' => 10000,
            'parcelas' => [1],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'CARTAO',
        ]);

        $this->actingAs($user);

        $this->get('/links-pagamento')->assertRedirect('/app/links-pagamento');
        $this->get('/links-pagamento/create')->assertRedirect('/app/links-pagamento/novo?tipo=CARTAO');
        $this->get('/links-pagamento/'.$link->id)->assertRedirect('/app/links-pagamento/'.$link->id.'/editar');
        $this->get('/links-pagamento/'.$link->id.'/edit')->assertRedirect('/app/links-pagamento/'.$link->id.'/editar');

        $this->get('/links-pagamento-pix')->assertRedirect('/app/links-pagamento');
        $this->get('/links-pagamento-pix/create')->assertRedirect('/app/links-pagamento/novo?tipo=PIX');
        $this->get('/links-pagamento-boleto')->assertRedirect('/app/links-pagamento');
        $this->get('/links-pagamento-boleto/create')->assertRedirect('/app/links-pagamento/novo?tipo=BOLETO');
    }

    public function test_legacy_establishment_routes_redirect_to_the_spa(): void
    {
        $admin = User::factory()->create([
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);

        PaytimeEstablishment::create([
            'id' => 9001,
            'fantasy_name' => 'Loja Legada',
            'email' => 'loja@example.com',
            'active' => true,
            'status' => 'APPROVED',
            'revenue' => 1000.00,
        ]);

        $this->actingAs($admin);

        $this->get('/estabelecimentos')->assertRedirect('/app/estabelecimentos');
        $this->get('/estabelecimentos/9001')->assertRedirect('/app/estabelecimentos/9001');
        $this->get('/estabelecimentos/9001/edit')->assertRedirect('/app/estabelecimentos/9001/editar');
    }

    public function test_legacy_cobranca_routes_redirect_to_the_spa(): void
    {
        $vendor = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $vendor->vendedor()->create([
            'estabelecimento_id' => '5001',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]);

        $this->actingAs($vendor);

        $this->get('/cobranca')->assertRedirect('/app/cobranca');
        $this->get('/cobranca/simular')->assertRedirect('/app/cobranca');
        $this->get('/cobranca/planos')->assertRedirect('/app/cobranca');
        $this->get('/cobranca/saldoextrato')->assertRedirect('/app/cobranca');
        $this->get('/cobranca/transacao/123')->assertRedirect('/app/cobranca');
        $this->get('/cobranca/boleto/123')->assertRedirect('/app/cobranca');
    }

    public function test_the_link_form_route_is_available(): void
    {
        $response = $this->get('/app/links-pagamento/novo');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_link_edit_route_is_available(): void
    {
        $response = $this->get('/app/links-pagamento/1/editar');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_establishment_edit_route_is_available(): void
    {
        $response = $this->get('/app/estabelecimentos/1/editar');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }
}
