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

    private function authenticateVendor(): User
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        return $user;
    }

    public function test_guest_users_are_redirected_to_the_login_page_from_the_spa_shell(): void
    {
        $response = $this->get('/app/cobranca/pix');

        $response->assertRedirect('/login');
    }

    public function test_the_spa_shell_is_available_at_the_app_route(): void
    {
        $this->authenticateVendor();

        $response = $this->get('/app');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_nested_spa_routes_return_the_same_shell(): void
    {
        $this->authenticateVendor();

        $response = $this->get('/app/painel');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_home_route_is_available(): void
    {
        $this->authenticateVendor();

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
        $this->authenticateVendor();

        $response = $this->get('/app/perfil');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_sidebar_contains_a_logout_button(): void
    {
        $shellSource = file_get_contents(base_path('resources/js/spa/layouts/AppShell.jsx'));
        $dashboardTemplateSource = file_get_contents(base_path('resources/views/templates/dashboard-template.blade.php'));
        $navigationSource = file_get_contents(base_path('resources/js/spa/navigation/menu.js'));
        $stylesSource = file_get_contents(base_path('resources/css/app.css'));
        $establishmentsPageSource = file_get_contents(base_path('resources/js/spa/pages/EstablishmentsPage.jsx'));
        $vendedoresAcessoPageSource = file_get_contents(base_path('resources/js/spa/pages/VendedoresAcessoPage.jsx'));

        $this->assertStringContainsString('action="/logout"', $shellSource);
        $this->assertStringContainsString('Sair', $shellSource);
        $this->assertStringContainsString('spa-sider-footer', $shellSource);
        $this->assertStringContainsString('height: 39.6', $shellSource);
        $this->assertStringContainsString('const [sidebarCollapsed, setSidebarCollapsed] = useState(false);', $shellSource);
        $this->assertStringContainsString('collapsed={sidebarCollapsed}', $shellSource);
        $this->assertStringContainsString('collapsedWidth={88}', $shellSource);
        $this->assertStringContainsString('inlineCollapsed={sidebarCollapsed}', $shellSource);
        $this->assertStringContainsString('Recolher menu lateral', $shellSource);
        $this->assertStringContainsString('Expandir menu lateral', $shellSource);
        $this->assertStringContainsString('HomeOutlined', $shellSource);
        $this->assertStringContainsString('LineChartOutlined', $shellSource);
        $this->assertStringContainsString('SafetyOutlined', $shellSource);
        $this->assertStringContainsString('HistoryOutlined', $shellSource);
        $this->assertStringContainsString('QrcodeOutlined', $shellSource);
        $this->assertStringContainsString('LinkOutlined', $shellSource);
        $this->assertStringContainsString('FileDoneOutlined', $shellSource);
        $this->assertStringContainsString('WalletOutlined', $shellSource);
        $this->assertStringContainsString('ShoppingOutlined', $shellSource);
        $this->assertStringContainsString('ShopOutlined', $shellSource);
        $this->assertStringContainsString('UserSwitchOutlined', $shellSource);
        $this->assertStringContainsString('UserOutlined', $shellSource);
        $this->assertStringNotContainsString('SettingOutlined', $shellSource);
        $this->assertStringContainsString('Em análise', $establishmentsPageSource);
        $this->assertStringContainsString('Senha obrigatória', $vendedoresAcessoPageSource);
        $this->assertStringContainsString('Não foi possível carregar o acesso dos vendedores.', $vendedoresAcessoPageSource);
        $this->assertStringContainsString('.spa-sider-collapsed', $stylesSource);
        $this->assertStringContainsString('.spa-sider-toggle', $stylesSource);
        $this->assertStringContainsString('.spa-sider-topbar', $stylesSource);
        $this->assertStringContainsString("icon: 'admin-estabelecimentos'", $navigationSource);
        $this->assertStringContainsString("icon: 'vendedores-estabelecimentos'", $navigationSource);
        $this->assertStringContainsString("icon: 'historico'", $navigationSource);
        $this->assertStringContainsString("icon: 'checkout-produtos'", $navigationSource);
        $this->assertStringContainsString("icon: 'checkout-links'", $navigationSource);
        $this->assertStringNotContainsString("icon: 'estabelecimentos'", $navigationSource);
        $this->assertStringNotContainsString("icon: 'unica'", $navigationSource);
        $this->assertStringNotContainsString("icon: 'checkout'", $navigationSource);
        $this->assertStringContainsString('style="height: 32.4px;"', $dashboardTemplateSource);
        $this->assertStringContainsString('/app/cobranca/cartao-credito', $dashboardTemplateSource);
        $this->assertStringContainsString('Hist', $navigationSource);

    }

    public function test_the_dashboard_fab_links_directly_to_the_spa_simulation_page(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => '5001',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
        ]);

        $this->actingAs($user);

        $response = $this->get('/cobranca');

        $response->assertOk();
        $response->assertSee('/app/cobranca/simular', false);
    }

    public function test_the_spa_shell_contains_the_simulation_fab_button(): void
    {
        $shellSource = file_get_contents(base_path('resources/js/spa/layouts/AppShell.jsx'));
        $homePageSource = file_get_contents(base_path('resources/js/spa/pages/HomePage.jsx'));

        $this->assertStringContainsString('CalculatorOutlined', $shellSource);
        $this->assertStringContainsString("navigate('/cobranca/simular')", $shellSource);
        $this->assertStringContainsString('spa-fab', $shellSource);
        $this->assertStringContainsString('Tooltip title="Simular transação"', $shellSource);
        $this->assertStringContainsString('width: 1.8em;', file_get_contents(base_path('resources/css/app.css')));
        $this->assertStringContainsString('title="Atualizar painel"', $homePageSource);
        $this->assertStringContainsString('Link to={card.href}', $homePageSource);
        $this->assertStringContainsString('/cobranca/saldoextrato', $homePageSource);
        $this->assertStringNotContainsString('className="spa-fab"', $homePageSource);
    }

    public function test_the_home_page_exposes_the_establishments_excel_export_link_for_admins(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/HomePage.jsx'));
        $stylesSource = file_get_contents(base_path('resources/css/app.css'));

        $this->assertStringContainsString('FileExcelFilled', $pageSource);
        $this->assertStringContainsString('/estabelecimentos/export', $pageSource);
        $this->assertStringContainsString("nivel_acesso === 'vendedor'", $pageSource);
        $this->assertStringContainsString("['admin', 'super_admin'].includes(payload.user?.nivel_acesso)", $pageSource);
        $this->assertStringContainsString("FileExcelFilled style={{ fontSize: '23.4px' }}", $pageSource);
        $this->assertStringContainsString('title="Exportar estabelecimentos"', $pageSource);
        $this->assertStringNotContainsString('Exportar estabelecimentos</Button>', $pageSource);
        $this->assertStringContainsString('.spa-dashboard-toolbar-excel-button.ant-btn', $stylesSource);
        $this->assertStringContainsString('color: #21a366', $stylesSource);
        $this->assertStringContainsString('font-size: 23.4px', $stylesSource);
    }

    public function test_the_home_page_exposes_the_clients_excel_export_link_for_vendors(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/HomePage.jsx'));

        $this->assertStringContainsString('/seller/clients/export', $pageSource);
        $this->assertStringContainsString('title="Exportar clientes"', $pageSource);
        $this->assertStringContainsString("payload.user?.nivel_acesso === 'vendedor'", $pageSource);
        $this->assertStringContainsString('FileExcelFilled style={{ fontSize: \'23.4px\' }}', $pageSource);
        $this->assertStringNotContainsString('Exportar clientes</Button>', $pageSource);
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
        $linksPagamentoPosition = strpos($navigationSource, 'links-pagamento.index');

        $this->assertNotFalse($historicoPosition);
        $this->assertNotFalse($pixPosition);
        $this->assertNotFalse($cartaoCreditoPosition);
        $this->assertNotFalse($boletoPosition);
        $this->assertNotFalse($linksPagamentoPosition);
        $this->assertLessThan($pixPosition, $historicoPosition);
        $this->assertLessThan($cartaoCreditoPosition, $pixPosition);
        $this->assertLessThan($boletoPosition, $cartaoCreditoPosition);
        $this->assertLessThan($linksPagamentoPosition, $boletoPosition);
    }

    public function test_the_removed_cobranca_sidebar_items_are_not_present(): void
    {
        $navigationSource = file_get_contents(base_path('resources/js/spa/navigation/menu.js'));

        $this->assertStringNotContainsString('cobranca.credito-vista', $navigationSource);
        $this->assertStringNotContainsString('links.cartao', $navigationSource);
        $this->assertStringNotContainsString('links.pix', $navigationSource);
        $this->assertStringNotContainsString('links.boleto', $navigationSource);
    }

    public function test_the_links_pagamento_sidebar_item_is_present_below_boleto(): void
    {
        $navigationSource = file_get_contents(base_path('resources/js/spa/navigation/menu.js'));

        $boletoPosition = strpos($navigationSource, 'cobranca.boleto');
        $linksPagamentoPosition = strpos($navigationSource, 'links-pagamento.index');

        $this->assertNotFalse($boletoPosition);
        $this->assertNotFalse($linksPagamentoPosition);
        $this->assertLessThan($linksPagamentoPosition, $boletoPosition);
        $this->assertStringContainsString("label: 'Links de Pagamento'", $navigationSource);
        $this->assertStringContainsString("path: '/links-pagamento'", $navigationSource);
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
        $this->authenticateVendor();

        foreach ([
            '/app/cobranca/pix',
            '/app/cobranca/credito-vista',
            '/app/cobranca/cartao-credito',
            '/app/cobranca/boleto',
            '/app/cobranca/boleto/example-id',
            '/app/cobranca/planos',
            '/app/cobranca/planos/123',
            '/app/cobranca/saldoextrato',
            '/app/cobranca/simular',
            '/app/links-pagamento/1',
            '/app/links-pagamento-pix/1',
        ] as $path) {
            $response = $this->get($path);

            $response->assertOk();
            $response->assertSee('id="app"', false);
        }
    }

    public function test_the_saldo_extrato_page_contains_the_new_balance_layout(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/cobranca/CobrancaSaldoExtratoPage.jsx'));

        $this->assertStringContainsString('Saldo e extrato', $pageSource);
        $this->assertStringContainsString('Saldo disponível', $pageSource);
        $this->assertStringContainsString('Saldo bloqueado', $pageSource);
        $this->assertStringContainsString('Saldo total', $pageSource);
        $this->assertStringContainsString('Movimentações', $pageSource);
        $this->assertStringContainsString('Extrato do estabelecimento', $pageSource);
        $this->assertStringContainsString('Resumo financeiro', $pageSource);
        $this->assertStringContainsString('Nenhuma movimentação encontrada no extrato.', $pageSource);
        $this->assertStringNotContainsString("'Modalidade'", $pageSource);
        $this->assertStringContainsString('Voltar', $pageSource);
        $this->assertStringContainsString('spa-saldoextrato-sidebar-card', $pageSource);
        $this->assertStringContainsString('spa-saldoextrato-table-card', $pageSource);
        $this->assertStringNotContainsString('placeholder', $pageSource);
        $this->assertStringNotContainsString('Espaço reservado para saldos', $pageSource);
    }

    public function test_the_pix_page_uses_the_new_link_payment_modal_labels(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/cobranca/CobrancaPixPage.jsx'));
        $this->assertStringContainsString('Gerar QR Code', $pageSource);
        $this->assertStringContainsString('spa-pix-collapse-label-badge', $pageSource);
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
        $this->assertStringNotContainsString('Atualizar painel', $pageSource);
        $this->assertStringContainsString('const [recentLinksState, setRecentLinksState] = useState([]);', $pageSource);
        $this->assertStringContainsString('refreshRecentLinks();', $pageSource);
        $this->assertStringContainsString('data.recent_links ?? []', $pageSource);
        $this->assertStringContainsString("filter((item) => item.raw_type === 'PIX' || item.type === 'PIX')", $pageSource);
        $this->assertStringContainsString('async function refreshRecentLinks()', $pageSource);
        $this->assertStringContainsString("fetch('/api/spa/links-pagamento'", $pageSource);
        $this->assertStringContainsString('await refreshRecentLinks();', $pageSource);
        $this->assertStringContainsString('const pixSummary = useMemo(() => ({', $pageSource);
        $this->assertStringContainsString('total_transactions: pixTransactionRows.length', $pageSource);
        $this->assertStringContainsString(')).length + activePixLinksCount', $pageSource);
        $this->assertStringContainsString("['Transações', pixSummary.total_transactions]", $pageSource);
        $this->assertStringContainsString("['Pagas', pixSummary.paid_transactions]", $pageSource);
        $this->assertStringNotContainsString('spa-pix-empty-card', $pageSource);
        $this->assertStringContainsString('recentLinks.slice(0, 2).map((item) => (', $pageSource);
        $this->assertStringNotContainsString('recentLinks.slice(0, 5).map((item) => (', $pageSource);
        $this->assertStringContainsString('recent_links', $pageSource);
        $this->assertStringNotContainsString('overview.recent_links', $pageSource);
        $this->assertStringContainsString('role="button"', $pageSource);
        $this->assertStringContainsString('tabIndex={0}', $pageSource);
        $this->assertStringNotContainsString('Abrir', $pageSource);
    }

    public function test_the_pix_page_shows_status_below_the_transaction_date(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/cobranca/CobrancaPixPage.jsx'));

        $this->assertStringContainsString("title: 'Data'", $pageSource);
        $this->assertStringContainsString('dataIndex: \'created_at\'', $pageSource);
        $this->assertStringContainsString('render: (value, record) =>', $pageSource);
        $this->assertStringContainsString('<Typography.Text>{value}</Typography.Text>', $pageSource);
        $this->assertStringContainsString('function getPaymentStatus(record)', $pageSource);
        $this->assertStringContainsString("return record.raw_status === 'PAID' || record.status === 'Pago' ? 'Pago' : 'Pendente';", $pageSource);
        $this->assertStringContainsString('<Tag color={getStatusColor(getPaymentStatus(record))}>{getPaymentStatus(record)}</Tag>', $pageSource);
        $this->assertStringContainsString("case 'Ativo':\n                return 'gold';", $pageSource);
        $this->assertStringContainsString("case 'Inativo':\n                return 'red';", $pageSource);
    }

    public function test_the_boleto_page_contains_the_form_sections(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/cobranca/CobrancaBoletoPage.jsx'));

        $this->assertStringContainsString('Criar boleto', $pageSource);
        $this->assertStringContainsString('Gerar Boleto', $pageSource);
        $this->assertStringContainsString('Valor do boleto', $pageSource);
        $this->assertStringContainsString('Data limite para pagamento', $pageSource);
        $this->assertStringContainsString('Dados do cliente', $pageSource);
        $this->assertStringContainsString('Instrues do boleto', $pageSource);
        $this->assertStringContainsString('Fechar', $pageSource);
        $this->assertStringContainsString('Criar boleto', $pageSource);
        $this->assertStringContainsString('Boletos do ms', $pageSource);
        $this->assertStringContainsString('spa-pix-page-toggle-button', $pageSource);
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
        $this->assertStringContainsString('Criar boleto', $pageSource);
        $this->assertStringContainsString('Ver histórico', $pageSource);
        $this->assertStringContainsString('openBoletoDetails(item)', $pageSource);
    }

    public function test_the_cartao_credito_page_contains_the_new_card_cobranca_structure(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/cobranca/CobrancaCartaoCreditoPage.jsx'));

        $this->assertStringContainsString('Gerar Cobrança', $pageSource);
        $this->assertStringContainsString('Valor da cobrança', $pageSource);
        $this->assertStringContainsString('Dados do cliente', $pageSource);
        $this->assertStringContainsString('Dados do cartão', $pageSource);
        $this->assertStringContainsString('Visão rápida', $pageSource);
        $this->assertStringNotContainsString('Painel lateral', $pageSource);
        $this->assertStringContainsString('Atualizar painel', $pageSource);
        $this->assertStringContainsString('spa-cartao-credito-collapse', $pageSource);
        $this->assertStringContainsString("style={{ minWidth: 176, width: 'auto' }}", $pageSource);
        $this->assertStringNotContainsString('ComingSoonPage', $pageSource);
        $this->assertStringContainsString('const yearOptions = Array.from({ length: 10 }, (_, index) => {', $pageSource);
        $this->assertStringContainsString('const year = new Date().getFullYear() + index;', $pageSource);
        $this->assertStringNotContainsString('length: 6', $pageSource);
        $this->assertStringContainsString('const creditSummary = useMemo(() => {', $pageSource);
        $this->assertStringContainsString("approved_transactions: creditRows.filter((row) => ['PAID', 'APPROVED'].includes(row.raw_status)).length", $pageSource);
        $this->assertStringContainsString("pending_transactions: creditRows.filter((row) => ['PENDING', 'PROCESSING'].includes(row.raw_status)).length", $pageSource);
        $this->assertStringContainsString("['Aprovadas', creditSummary.approved_transactions]", $pageSource);
        $this->assertStringContainsString("['Pendentes', creditSummary.pending_transactions]", $pageSource);
        $this->assertStringNotContainsString("['Aprovadas', summary.paid_transactions ?? 0]", $pageSource);
        $this->assertLessThan(
            strpos($pageSource, 'label="Rua"'),
            strpos($pageSource, 'label="CEP"')
        );
        $this->assertLessThan(
            strpos($pageSource, 'label="Complemento"'),
            strpos($pageSource, 'label="Número"')
        );
        $this->assertSame(1, substr_count($pageSource, "name={['client', 'address', 'number']}"));
        $this->assertLessThan(
            strpos($pageSource, 'label="Bairro"'),
            strpos($pageSource, 'label="Complemento"')
        );
        $this->assertLessThan(
            strpos($pageSource, 'label="Cidade"'),
            strpos($pageSource, 'label="Estado"')
        );
    }

    public function test_the_cartao_credito_page_keeps_recent_links_at_two_items_and_refreshes_only_after_creation(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/cobranca/CobrancaCartaoCreditoPage.jsx'));

        $this->assertStringContainsString('const [recentLinksState, setRecentLinksState] = useState([]);', $pageSource);
        $this->assertStringContainsString('setRecentLinksState((current) => (current.length === 0 ? (data.recent_card_links ?? []).slice(0, 2) : current));', $pageSource);
        $this->assertStringContainsString('async function refreshRecentLinks()', $pageSource);
        $this->assertStringContainsString('fetch(`/api/spa/cobranca', $pageSource);
        $this->assertStringContainsString('await refreshRecentLinks();', $pageSource);
        $this->assertStringContainsString('recentLinks.slice(0, 2).map((item) => (', $pageSource);
        $this->assertStringNotContainsString('recentLinks.slice(0, 5).map((item) => (', $pageSource);
        $this->assertStringNotContainsString('overview.recent_links', $pageSource);
        $this->assertStringContainsString('recent_card_links', $pageSource);
        $this->assertStringContainsString('role="button"', $pageSource);
        $this->assertStringContainsString('tabIndex={0}', $pageSource);
        $this->assertStringNotContainsString('Abrir', $pageSource);
    }

    public function test_the_pix_link_detail_page_contains_the_extended_sections(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/LinkPagamentoPixDetailPage.jsx'));

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

    public function test_the_links_pagamento_detail_page_contains_the_action_sections(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/LinkPagamentoDetailPage.jsx'));

        $this->assertStringContainsString('Detalhes do link de pagamento', $pageSource);
        $this->assertStringContainsString('Copiar link', $pageSource);
        $this->assertStringContainsString('Testar link', $pageSource);
        $this->assertStringContainsString('Editar', $pageSource);
        $this->assertStringContainsString('Desativar', $pageSource);
        $this->assertStringContainsString('Ativar', $pageSource);
        $this->assertStringContainsString('Excluir', $pageSource);
        $this->assertStringContainsString('Dados do cliente', $pageSource);
        $this->assertStringContainsString('Instruções do boleto', $pageSource);
        $this->assertStringContainsString('navigate(`/links-pagamento/${linkId}`)', $pageSource);
        $this->assertStringContainsString('fetch(`/links-pagamento/${linkId}/status`', $pageSource);
        $this->assertStringContainsString('fetch(`/links-pagamento/${linkId}`', $pageSource);
    }

    public function test_the_links_pagamento_list_page_opens_the_detail_page_on_click(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/LinksPagamentoPage.jsx'));

        $this->assertStringContainsString('navigate(buildDetailHref(record))', $pageSource);
        $this->assertStringContainsString('navigate(buildDetailHref(item))', $pageSource);
        $this->assertStringContainsString('Ações e atalhos', $pageSource);
        $this->assertStringContainsString('Abrir detalhes', $pageSource);
        $this->assertStringNotContainsString('Atualizar', $pageSource);
        $this->assertStringNotContainsString('ThunderboltOutlined', $pageSource);
    }

    public function test_the_cobranca_route_is_available(): void
    {
        $this->authenticateVendor();

        $response = $this->get('/app/cobranca');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_links_pagamento_route_is_available(): void
    {
        $this->authenticateVendor();

        $response = $this->get('/app/links-pagamento');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_vendedores_route_is_available(): void
    {
        $this->authenticateVendor();

        $response = $this->get('/app/vendedores');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_establishment_details_route_is_available(): void
    {
        $this->authenticateVendor();

        $response = $this->get('/app/estabelecimentos/1');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_vendedores_access_route_is_available(): void
    {
        $this->authenticateVendor();

        $response = $this->get('/app/vendedores/acesso');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_vendedores_faturamento_route_is_available(): void
    {
        $this->authenticateVendor();

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
        $this->get('/links-pagamento/'.$link->id)->assertRedirect('/app/links-pagamento/'.$link->id);
        $this->get('/links-pagamento/'.$link->id.'/edit')->assertRedirect('/app/links-pagamento/'.$link->id.'/editar');

        $this->get('/links-pagamento-pix')->assertRedirect('/app/links-pagamento');
        $this->get('/links-pagamento-pix/create')->assertRedirect('/app/links-pagamento/novo?tipo=PIX');
        $this->get('/links-pagamento-pix/'.$link->id)->assertRedirect('/app/links-pagamento-pix/'.$link->id);
        $this->get('/links-pagamento-boleto')->assertRedirect('/app/links-pagamento');
        $this->get('/links-pagamento-boleto/create')->assertRedirect('/app/links-pagamento/novo?tipo=BOLETO');
        $this->get('/links-pagamento-boleto/'.$link->id)->assertRedirect('/app/links-pagamento/'.$link->id);
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

        $this->get('/cobranca/simular')->assertRedirect('/app/cobranca/simular');
        $this->get('/cobranca/cartao-credito')->assertRedirect('/app/cobranca/cartao-credito');
        $this->get('/cobranca/planos')->assertRedirect('/app/cobranca/planos');
        $this->get('/cobranca/planos/123')->assertRedirect('/app/cobranca/planos/123');
        $this->get('/cobranca/saldoextrato')->assertRedirect('/app/cobranca/saldoextrato');
    }

    public function test_the_link_form_route_is_available(): void
    {
        $this->authenticateVendor();

        $response = $this->get('/app/links-pagamento/novo');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_link_edit_route_is_available(): void
    {
        $this->authenticateVendor();

        $response = $this->get('/app/links-pagamento/1/editar');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_establishment_edit_route_is_available(): void
    {
        $this->authenticateVendor();

        $response = $this->get('/app/estabelecimentos/1/editar');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }
}
