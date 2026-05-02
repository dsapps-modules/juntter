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

    public function test_the_legacy_home_route_redirects_to_the_spa_home_route(): void
    {
        $response = $this->get('/home');

        $response->assertRedirect('/app/home');
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

    public function test_the_profile_route_is_available(): void
    {
        $response = $this->get('/app/perfil');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_sidebar_contains_a_logout_button(): void
    {
        $shellSource = file_get_contents(base_path('resources/js/spa/layouts/AppShell.jsx'));
        $dashboardTemplateSource = file_get_contents(base_path('resources/views/templates/dashboard-template.blade.php'));
        $navigationSource = file_get_contents(base_path('resources/js/spa/navigation/menu.js'));

        $this->assertStringContainsString('action="/logout"', $shellSource);
        $this->assertStringContainsString('Sair', $shellSource);
        $this->assertStringContainsString('spa-sider-footer', $shellSource);
        $this->assertStringContainsString('height: 39.6', $shellSource);
        $this->assertStringContainsString('UserSwitchOutlined', $shellSource);
        $this->assertStringContainsString('UserOutlined', $shellSource);
        $this->assertStringNotContainsString('SettingOutlined', $shellSource);
        $this->assertStringContainsString('style="height: 32.4px;"', $dashboardTemplateSource);
        $this->assertStringContainsString('Hist', $navigationSource);

    }

    public function test_the_top_sidebar_items_are_back_in_home_before_cobranca(): void
    {
        $navigationSource = file_get_contents(base_path('resources/js/spa/navigation/menu.js'));
        $vendedorSectionStart = strpos($navigationSource, 'vendedor: [');
        $sharedItemsStart = strpos($navigationSource, 'export const sharedNavigationItems');
        $vendedorSection = substr(
            $navigationSource,
            $vendedorSectionStart === false ? 0 : $vendedorSectionStart,
            $sharedItemsStart === false || $vendedorSectionStart === false ? null : $sharedItemsStart - $vendedorSectionStart
        );

        $saldoPosition = strpos($vendedorSection, 'cobranca.saldo');
        $saldoRoutePosition = strpos($vendedorSection, '/cobranca/saldoextrato');
        $simularRoutePosition = strpos($vendedorSection, '/cobranca/simular');
        $cobrancaHeaderPosition = strpos($vendedorSection, "label: 'Cobran");
        $simularPosition = strpos($vendedorSection, 'cobranca.simular');
        $this->assertNotFalse($saldoPosition);
        $this->assertNotFalse($saldoRoutePosition);
        $this->assertNotFalse($simularRoutePosition);
        $this->assertNotFalse($simularPosition);
        $this->assertNotFalse($cobrancaHeaderPosition);
        $this->assertLessThan($simularPosition, $saldoPosition);
        $this->assertLessThan($cobrancaHeaderPosition, $simularPosition);
    }

    public function test_the_cobranca_sidebar_items_are_in_the_expected_order(): void
    {
        $navigationSource = file_get_contents(base_path('resources/js/spa/navigation/menu.js'));

        $historicoPosition = strpos($navigationSource, 'cobranca.unica');
        $pixPosition = strpos($navigationSource, 'cobranca.pix');
        $cartaoCreditoPosition = strpos($navigationSource, 'cobranca.cartao-credito');
        $boletoPosition = strpos($navigationSource, 'cobranca.boleto');

        $this->assertNotFalse($historicoPosition);
        $this->assertNotFalse($pixPosition);
        $this->assertNotFalse($cartaoCreditoPosition);
        $this->assertNotFalse($boletoPosition);
        $this->assertLessThan($pixPosition, $historicoPosition);
        $this->assertLessThan($cartaoCreditoPosition, $pixPosition);
        $this->assertLessThan($boletoPosition, $cartaoCreditoPosition);
    }

    public function test_the_removed_cobranca_sidebar_items_are_not_present(): void
    {
        $navigationSource = file_get_contents(base_path('resources/js/spa/navigation/menu.js'));

        $this->assertStringNotContainsString('cobranca.credito-vista', $navigationSource);
        $this->assertStringNotContainsString('links.cartao', $navigationSource);
        $this->assertStringNotContainsString('links.pix', $navigationSource);
        $this->assertStringNotContainsString('links.boleto', $navigationSource);
        $this->assertStringNotContainsString("label: 'Links de Pagamento'", $navigationSource);
    }

    public function test_the_plano_contratado_item_is_hidden_for_admins_and_kept_for_vendors(): void
    {
        $navigationSource = file_get_contents(base_path('resources/js/spa/navigation/menu.js'));

        $this->assertStringContainsString('getSharedNavigationItems(role)', $navigationSource);
        $this->assertStringContainsString("role !== 'admin' && role !== 'super_admin'", $navigationSource);
        $this->assertStringContainsString("label: 'Plano Contratado'", $navigationSource);
        $this->assertStringContainsString("label: 'Perfil'", $navigationSource);
    }

    public function test_the_new_cobranca_pages_are_available(): void
    {
        foreach ([
            '/app/cobranca/pix',
            '/app/cobranca/credito-vista',
            '/app/cobranca/cartao-credito',
            '/app/cobranca/boleto',
            '/app/cobranca/planos',
            '/app/cobranca/planos/123',
            '/app/cobranca/saldoextrato',
            '/app/cobranca/simular',
            '/app/links-pagamento-pix/1',
        ] as $path) {
            $response = $this->get($path);

            $response->assertOk();
            $response->assertSee('id="app"', false);
        }
    }

    public function test_the_saldo_extrato_page_contains_the_placeholder_layout(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/cobranca/CobrancaSaldoExtratoPage.jsx'));

        $this->assertStringContainsString('Extrato do período', $pageSource);
        $this->assertStringContainsString('Resumo financeiro', $pageSource);
        $this->assertStringContainsString('Saldo atual', $pageSource);
        $this->assertStringContainsString('Lançamentos futuros', $pageSource);
        $this->assertStringContainsString('Sem lançamentos para o período selecionado.', $pageSource);
        $this->assertStringContainsString('Selecionar mês', $pageSource);
        $this->assertStringContainsString('Selecionar ano', $pageSource);
        $this->assertStringContainsString('spa-saldoextrato-sidebar-card', $pageSource);
        $this->assertStringContainsString('spa-saldoextrato-table-card', $pageSource);
        $this->assertStringNotContainsString('Conta corrente', $pageSource);
        $this->assertStringNotContainsString('Espaço reservado para saldos', $pageSource);
    }

    public function test_the_pix_page_uses_the_new_link_payment_modal_labels(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/cobranca/CobrancaPixPage.jsx'));

        $this->assertStringContainsString('Link de Pagamento', $pageSource);
        $this->assertStringContainsString('Gerar QR Code', $pageSource);
        $this->assertStringContainsString('spa-pix-collapse-label-badge', $pageSource);
        $this->assertStringContainsString('spa-pix-page-link-button', $pageSource);
        $this->assertStringContainsString('spa-pix-page-toggle-button', $pageSource);
        $this->assertStringContainsString('spa-pix-form-panel', $pageSource);
        $this->assertStringNotContainsString('Collapse', $pageSource);
        $this->assertStringNotContainsString('Abra para montar o PIX', $pageSource);
        $this->assertStringContainsString('Descreva o que o cliente', $pageSource);
    }

    public function test_the_pix_page_contains_the_side_panel_content(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/cobranca/CobrancaPixPage.jsx'));

        $this->assertStringContainsString('Visão rápida', $pageSource);
        $this->assertStringNotContainsString('Painel lateral', $pageSource);
        $this->assertStringContainsString('Atalhos', $pageSource);
        $this->assertStringContainsString('Últimos links', $pageSource);
        $this->assertStringContainsString('Criar link PIX', $pageSource);
        $this->assertStringContainsString('Ver links', $pageSource);
        $this->assertStringContainsString('Atualizar painel', $pageSource);
        $this->assertStringNotContainsString('spa-pix-empty-card', $pageSource);
    }

    public function test_the_boleto_page_contains_the_form_sections(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/cobranca/CobrancaBoletoPage.jsx'));

        $this->assertStringContainsString('Criar boleto', $pageSource);
        $this->assertStringContainsString('Gerar Boleto', $pageSource);
        $this->assertStringContainsString('Link de Pagamento', $pageSource);
        $this->assertStringContainsString('Valor do boleto', $pageSource);
        $this->assertStringContainsString('Data de vencimento', $pageSource);
        $this->assertStringContainsString('Data limite para pagamento', $pageSource);
        $this->assertStringContainsString('Dados do cliente', $pageSource);
        $this->assertStringContainsString('Endereço do cliente', $pageSource);
        $this->assertStringContainsString('Instruções do boleto', $pageSource);
        $this->assertStringContainsString('É carnê?', $pageSource);
        $this->assertStringContainsString('Multa por atraso', $pageSource);
        $this->assertStringContainsString('Juros ao mês', $pageSource);
        $this->assertStringContainsString('Data limite para desconto', $pageSource);
        $this->assertStringContainsString('Fechar', $pageSource);
        $this->assertStringContainsString('Gerar boleto', $pageSource);
        $this->assertStringContainsString('Boletos do mês', $pageSource);
        $this->assertStringContainsString('Link de Pagamento - Boleto', $pageSource);
        $this->assertStringContainsString('spa-pix-page-toggle-button', $pageSource);
        $this->assertStringContainsString('spa-pix-page-link-button', $pageSource);
        $this->assertStringContainsString('spa-pix-transactions-table', $pageSource);
        $this->assertStringNotContainsString('Abra para emitir um novo boleto com os dados do cliente', $pageSource);
        $this->assertStringNotContainsString('spa-pix-collapse-label-badge">Gerar Boleto', $pageSource);
        $this->assertStringNotContainsString('Atualizar dados', $pageSource);
        $this->assertStringNotContainsString('ComingSoonPage', $pageSource);
    }

    public function test_the_boleto_page_contains_the_contextual_side_panel_content(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/cobranca/CobrancaBoletoPage.jsx'));

        $this->assertStringContainsString('Visão rápida', $pageSource);
        $this->assertStringNotContainsString('Painel lateral', $pageSource);
        $this->assertStringContainsString('Atalhos', $pageSource);
        $this->assertStringContainsString('Últimos boletos', $pageSource);
        $this->assertStringContainsString('Gerar boleto', $pageSource);
        $this->assertStringContainsString('Criar link de pagamento', $pageSource);
        $this->assertStringContainsString('Ver links', $pageSource);
        $this->assertStringContainsString('Atualizar painel', $pageSource);
    }

    public function test_the_cartao_credito_page_contains_the_new_card_cobranca_structure(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/cobranca/CobrancaCartaoCreditoPage.jsx'));

        $this->assertStringContainsString('Gerar Cobrança', $pageSource);
        $this->assertStringContainsString('Link de Pagamento', $pageSource);
        $this->assertStringContainsString('Valor da cobrança', $pageSource);
        $this->assertStringContainsString('Dados do cliente', $pageSource);
        $this->assertStringContainsString('Dados do cartão', $pageSource);
        $this->assertStringContainsString('Link de Pagamento - Cartão de Crédito', $pageSource);
        $this->assertStringContainsString('Visão rápida', $pageSource);
        $this->assertStringNotContainsString('Painel lateral', $pageSource);
        $this->assertStringContainsString('Atualizar painel', $pageSource);
        $this->assertStringContainsString('spa-cartao-credito-collapse', $pageSource);
        $this->assertStringContainsString("style={{ minWidth: 176, width: 'auto' }}", $pageSource);
        $this->assertStringNotContainsString('ComingSoonPage', $pageSource);
    }

    public function test_the_pix_link_detail_page_contains_the_extended_sections(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/LinkPagamentoPixDetailPage.jsx'));

        $this->assertStringContainsString('Link de Pagamento PIX', $pageSource);
        $this->assertStringContainsString('Voltar', $pageSource);
        $this->assertStringContainsString('/cobranca/pix', $pageSource);
        $this->assertStringContainsString('Resumo do link', $pageSource);
        $this->assertStringContainsString('Acoes recomendadas', $pageSource);
        $this->assertStringContainsString('Dica rapida', $pageSource);
        $this->assertStringContainsString('Copie a URL completa', $pageSource);
        $this->assertStringContainsString('Desativar', $pageSource);
        $this->assertStringContainsString('Excluir', $pageSource);
        $this->assertStringNotContainsString('Editar', $pageSource);
        $this->assertStringNotContainsString('HomeOutlined', $pageSource);
        $this->assertStringNotContainsString("onClick={() => navigate('/links-pagamento')}", $pageSource);
        $this->assertStringNotContainsString('Pre-preenchido:', $pageSource);
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

    public function test_legacy_vendedores_root_route_redirects_to_the_spa(): void
    {
        $admin = User::factory()->create([
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin);

        $this->get('/vendedores')->assertRedirect('/app/vendedores');
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
        $this->get('/links-pagamento-pix/'.$link->id)->assertRedirect('/app/links-pagamento-pix/'.$link->id);
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
        $this->get('/cobranca/simular')->assertRedirect('/app/cobranca/simular');
        $this->get('/cobranca/planos')->assertRedirect('/app/cobranca/planos');
        $this->get('/cobranca/planos/123')->assertRedirect('/app/cobranca/planos/123');
        $this->get('/cobranca/saldoextrato')->assertRedirect('/app/cobranca/saldoextrato');
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
