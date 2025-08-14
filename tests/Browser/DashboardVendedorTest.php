<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Vendedor;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DashboardVendedorTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Cria um usuário vendedor admin de loja
     */
    private function createVendedorAdminLoja(): User
    {
        $user = User::create([
            'name' => 'Vendedor Admin Loja',
            'email' => 'vendedor.admin@test.com',
            'password' => Hash::make('senha123456'),
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        Vendedor::create([
            'user_id' => $user->id,
            'estabelecimento_id' => '155161', // ID real - DS Aplicativos
            'sub_nivel' => 'admin_loja',
            'comissao' => 5.00,
            'meta_vendas' => 10000.00,
            'telefone' => '(11) 99999-9999',
            'endereco' => 'Rua Teste, 123',
            'status' => 'ativo'
        ]);

        return $user;
    }

        /**
     * Cria um usuário vendedor admin da Juntter
     */
    private function createVendedorAdminJuntter(): User
    {
        $user = User::create([
            'name' => 'Admin Juntter',
            'email' => 'admin.juntter@test.com',
            'password' => Hash::make('senha123456'),
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        Vendedor::create([
            'user_id' => $user->id,
            'estabelecimento_id' => '155102', // ID real - Juntter
            'sub_nivel' => 'admin_loja',
            'comissao' => 4.50,
            'meta_vendas' => 40000.00,
            'telefone' => '(11) 77777-7777',
            'endereco' => 'Rua das Margaridas, 789',
            'status' => 'ativo'
        ]);

        return $user;
    }

    /**
     * Cria um usuário vendedor de loja
     */
    private function createVendedorLoja(): User
    {
        $user = User::create([
            'name' => 'Vendedor Loja',
            'email' => 'vendedor.loja@test.com',
            'password' => Hash::make('senha123456'),
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        Vendedor::create([
            'user_id' => $user->id,
            'estabelecimento_id' => '155161', // ID real - DS Aplicativos
            'sub_nivel' => 'vendedor_loja',
            'comissao' => 3.00,
            'meta_vendas' => 5000.00,
            'telefone' => '(11) 88888-8888',
            'endereco' => 'Rua Teste, 456',
            'status' => 'ativo'
        ]);

        return $user;
    }

       /**
     * Teste de verificação do breadcrumb conforme estabelecimento do usuário
     */
    public function test_breadcrumb_estabelecimento_correto_vendedor()
    {
        // Primeiro usuário - DS Aplicativos
        $userDS = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($userDS) {
            $browser->loginAs($userDS)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                
                // Verifica se o breadcrumb mostra informações do estabelecimento DS Aplicativos
                ->assertPresent('nav[aria-label="breadcrumb"]')
                ->assertSee('Estabelecimento')
                ->assertSee('ID')
                ->assertSee('155161') // ID do DS Aplicativos
                ->pause(1000);
        });

        // Faz logout
        $this->browse(function (Browser $browser) {
            $browser->click('#userDropdown')
                ->pause(500)
                ->click('button[type="submit"]')
                ->pause(2000)
                ->assertPathIs('/');
        });

        // Segundo usuário - Juntter
        $userJuntter = $this->createVendedorAdminJuntter();

        $this->browse(function (Browser $browser) use ($userJuntter) {
            $browser->loginAs($userJuntter)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                
                // Verifica se o breadcrumb mostra informações do estabelecimento Juntter
                ->assertPresent('nav[aria-label="breadcrumb"]')
                ->assertSee('Estabelecimento')
                ->assertSee('ID')
                ->assertSee('155102') // ID da Juntter
                ->pause(1000);
        });
    }

    /**
     * Teste básico do dashboard vendedor admin de loja
     */
    public function test_dashboard_vendedor_admin_loja_basico()
    {
        $user = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                ->assertPathIs('/vendedor/dashboard')

                ->assertSee('Dashboard')
                ->assertPresent('.saldo-card')
                ->assertPresent('.analytics-tabs')
                ->assertPresent('#tabela-transacoes');
        });
    }

    /**
     * Teste básico do dashboard vendedor de loja
     */
    public function test_dashboard_vendedor_loja_basico()
    {
        $user = $this->createVendedorLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                ->assertPathIs('/vendedor/dashboard')

                ->assertSee('Dashboard')
                ->assertPresent('.analytics-tabs')
                ->assertPresent('#tabela-transacoes');
        });
    }

    /**
     * Teste das abas de analytics para vendedor
     */
    public function test_abas_analytics_vendedor()
    {
        $user = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                ->assertPresent('#tab-content-geral')
                ->assertVisible('#tab-content-geral')
                ->assertPresent('.analytics-tabs .tab-btn:nth-child(1)')
                ->assertPresent('.analytics-tabs .tab-btn:nth-child(2)')
                ->assertPresent('.analytics-tabs .tab-btn:nth-child(3)');

            // Teste aba Cartão
            $browser->click('.analytics-tabs .tab-btn:nth-child(2)')
                ->pause(500)
                ->assertVisible('#tab-content-cartao')
                ->assertMissing('#tab-content-geral');

            // Teste aba Boleto
            $browser->click('.analytics-tabs .tab-btn:nth-child(3)')
                ->pause(500)
                ->assertVisible('#tab-content-boleto')
                ->assertMissing('#tab-content-cartao');

            // Volta para aba Geral
            $browser->click('.analytics-tabs .tab-btn:nth-child(1)')
                ->pause(500)
                ->assertVisible('#tab-content-geral')
                ->assertMissing('#tab-content-boleto');
        });
    }

    /**
     * Teste da tabela de transações
     */
    public function test_tabela_transacoes_vendedor()
    {
        $user = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                ->scrollTo('#tabela-transacoes')
                ->pause(1000)

                ->assertPresent('#tabela-transacoes')
                ->assertPresent('#tabela-transacoes thead')
                ->assertPresent('#tabela-transacoes tbody');
        });
    }

    /**
     * Teste dos botões de ação da tabela - Ver detalhes
     */
    public function test_botao_ver_detalhes_transacao_vendedor()
    {
        $user = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                ->scrollTo('#tabela-transacoes')
                ->pause(1000)

                // Verifica se existe pelo menos um botão de ver detalhes
                ->assertPresent('#tabela-transacoes .btn-outline-info')
                ->assertPresent('#tabela-transacoes .fa-eye')

                // Clica no botão de ver detalhes
                ->click('#tabela-transacoes .btn-outline-info')
                ->pause(2000)

                // Verifica se foi redirecionado para a página de detalhes
                ->assertPathIs('/cobranca/transacao/*');
        });
    }

    /**
     * Teste de presença dos botões de ação na tabela
     */
    public function test_botoes_acao_tabela_vendedor()
    {
        $user = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                ->scrollTo('#tabela-transacoes')
                ->pause(1000)

                // Verifica se a tabela existe e tem conteúdo
                ->assertPresent('#tabela-transacoes')
                ->assertPresent('#tabela-transacoes tbody')
                
                // Verifica se existe pelo menos uma linha na tabela
                ->assertPresent('#tabela-transacoes tbody tr')

                // Verifica se os botões estão presentes 
                ->assertPresent('#tabela-transacoes .btn-group')
                ->assertPresent('#tabela-transacoes .btn-outline-info')
                ->assertPresent('#tabela-transacoes .fa-eye')

                // Verifica se os botões têm os títulos corretos 
                ->assertAttribute('#tabela-transacoes .btn-outline-info', 'title', 'Ver detalhes');
        });
    }

    /**
     * Teste do breadcrumb
     */
    public function test_breadcrumb_vendedor()
    {
        $user = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                ->assertPresent('nav[aria-label="breadcrumb"]')
                ->assertPresent('nav[aria-label="breadcrumb"] a[href*="dashboard"]')
                ->assertSee('Vendas');
        });
    }

    /**
     * Teste do menu do usuário
     */
    public function test_menu_usuario_vendedor()
    {
        $user = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                ->assertPresent('#userDropdown')
                ->assertSee($user->name)
                ->click('#userDropdown')
                ->pause(500)
                ->assertVisible('.dropdown-menu.show')
                ->assertSee('Editar Perfil')
                ->assertSee('Alterar Senha')
                ->assertSee('Sair');
        });
    }

    /**
     * Teste de navegação para editar perfil
     */
    public function test_navegacao_editar_perfil_vendedor()
    {
        $user = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                ->click('#userDropdown')
                ->pause(500)
                ->click('#editar-perfil')
                ->waitForLocation('/profile', 10)
                ->assertPathIs('/profile')
                
                // Verifica se os campos estão presentes
                ->assertPresent('input[name="name"]')
                ->assertPresent('input[name="email"]')
                ->assertPresent('button[type="submit"]')
                
                // Preenche os campos com novos dados
                ->clear('input[name="name"]')
                ->type('input[name="name"]', 'Vendedor Admin Atualizado')
                ->pause(500)
                ->clear('input[name="email"]')
                ->type('input[name="email"]', 'vendedor.atualizado@test.com')
                ->pause(500)
                
                // Verifica se os campos foram preenchidos
                ->assertValue('input[name="name"]', 'Vendedor Admin Atualizado')
                ->assertValue('input[name="email"]', 'vendedor.atualizado@test.com')
                ->pause(1000)
                
                // Submete o formulário usando seletor mais específico
                ->click('form[action*="profile"] button[type="submit"]')
                ->pause(3000)
                
                // Verifica se foi salvo com sucesso - deve mostrar mensagem "Perfil atualizado"
                ->assertPresent('input[name="name"]')
                ->assertPresent('input[name="email"]')
                ->assertSee('Perfil atualizado');
              
        });
    }

    /**
     * Teste de navegação para alterar senha
     */
    public function test_navegacao_alterar_senha_vendedor()
    {
        $user = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                ->click('#userDropdown')
                ->pause(500)
                ->click('#alterar-senha')
                ->waitForLocation('/profile/password', 10)
                ->assertPathIs('/profile/password')
                
                // Verifica se os campos estão presentes
                ->assertPresent('input[name="current_password"]')
                ->assertPresent('input[name="password"]')
                ->assertPresent('input[name="password_confirmation"]')
                ->assertPresent('button[type="submit"]')
                
                // Preenche os campos com as senhas
                ->type('input[name="current_password"]', 'senha123456')
                ->pause(500)
                ->type('input[name="password"]', 'novaSenha123456.')
                ->pause(500)
                ->type('input[name="password_confirmation"]', 'novaSenha123456.')
                ->pause(500)
                
                // Verifica se os campos foram preenchidos
                ->assertValue('input[name="current_password"]', 'senha123456')
                ->assertValue('input[name="password"]', 'novaSenha123456.')
                ->assertValue('input[name="password_confirmation"]', 'novaSenha123456.')
                ->pause(1000)
                
                // Submete o formulário usando seletor mais específico
                ->click('form[action*="password"] button[type="submit"]')
                ->pause(3000)
                
                // Verifica se foi salvo com sucesso - deve mostrar mensagem "Senha alterada"
                ->assertPresent('input[name="current_password"]')
                ->assertPresent('input[name="password"]')
                ->assertPresent('input[name="password_confirmation"]')
                ->assertSee('Senha atualizada');
               
        });
    }

    /**
     * Teste de logout
     */
    public function test_logout_vendedor()
    {
        $user = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                ->click('#userDropdown')
                ->pause(500)
                ->click('button[type="submit"]')
                ->pause(2000)
                ->assertPathIs('/');
        });
    }

    /**
     * Teste de responsividade da navbar
     */
    public function test_navbar_responsiva_vendedor()
    {
        $user = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                ->resize(375, 667)
                ->pause(1000)
                ->assertVisible('.navbar-toggler')
                ->click('.navbar-toggler')
                ->pause(500)
                ->assertVisible('.navbar-collapse.show')
                ->resize(1920, 1080)
                ->pause(1000);
        });
    }

    /**
     * Teste de métricas visíveis
     */
    public function test_metricas_visiveis_vendedor()
    {
        $user = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                ->assertPresent('.metric-card')
                ->assertPresent('.metric-value')
                ->assertPresent('.metric-label')
                ->assertPresent('.metric-icon');
        });
    }

    /**
     * Teste de navegação pelo breadcrumb
     */
    public function test_navegacao_breadcrumb_vendedor()
    {
        $user = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                ->click('nav[aria-label="breadcrumb"] a[href*="dashboard"]')
                ->waitForLocation('/vendedor/dashboard', 10)
                ->assertPathIs('/vendedor/dashboard');
        });
    }

    /**
     * Teste de carregamento completo da página
     */
    public function test_carregamento_completo_vendedor()
    {
        $user = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                ->assertPresent('.dashboard-content')
                ->assertPresent('.container')
                ->assertPresent('.navbar-brand')
                ->assertPresent('.logo-img')
                ->assertPresent('.dashboard-navbar')
                ->assertPresent('.analytics-card')
                ->assertPresent('.card')
                ->assertPresent('.table-responsive');
        });
    }

    /**
     * Teste de navegação para menu de cobrança
     */
    public function test_navegacao_menu_cobranca_vendedor()
    {
        $user = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                ->click('.dropdown-toggle')
                ->pause(500)
                ->assertVisible('.dropdown-menu.show')
                ->assertSee('Cobrança Única')
                ->assertSee('Planos de Cobrança')
                ->assertSee('Saldo e Extrato');
        });
    }

    /**
     * Teste de navegação para cobrança única
     */
    public function test_navegacao_cobranca_unica_vendedor()
    {
        $user = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                ->click('.dropdown-toggle')
                ->pause(500)
                ->click('a[href*="cobranca"]')
                ->waitForLocation('/cobranca', 10)
                ->assertPathIs('/cobranca');
        });
    }

    /**
     * Teste de navegação para planos de cobrança
     */
    public function test_navegacao_planos_cobranca_vendedor()
    {
        $user = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                ->click('.dropdown-toggle')
                ->pause(500)
                ->click('a[href*="cobranca/planos"]')
                ->waitForLocation('/cobranca/planos', 10)
                ->assertPathIs('/cobranca/planos');
        });
    }

    /**
     * Teste de navegação para saldo e extrato
     */
    public function test_navegacao_saldo_extrato_vendedor()
    {
        $user = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                ->click('.dropdown-toggle')
                ->pause(500)
                ->click('a[href*="cobranca/saldoextrato"]')
                ->waitForLocation('/cobranca/saldoextrato', 10)
                ->assertPathIs('/cobranca/saldoextrato');
        });
    }

    /**
     * Teste de criação de cobrança PIX
     */
    public function test_criar_cobranca_pix_vendedor()
    {
        $user = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/cobranca')
                ->pause(2000)
                ->assertPathIs('/cobranca')
                
                // Clica no botão para abrir o modal
                ->click('button[data-bs-target="#modalCobranca"]')
                ->pause(1000)
                
                // Verifica se o modal está aberto
                ->assertVisible('#modalCobranca')
                ->assertVisible('#link-content') // Aba PIX ativa
                
                // Preenche os dados do formulário PIX
                ->type('#modalCobranca input[name="amount"]', '10000') // R$ 100,00
                ->select('#modalCobranca select[name="interest"]', 'CLIENT') // Cliente paga taxas
                ->type('#modalCobranca input[name="client[first_name]"]', 'João')
                ->type('#modalCobranca input[name="client[last_name]"]', 'Silva')
                ->type('#modalCobranca input[name="client[document]"]', '77327746048')
                ->type('#modalCobranca input[name="client[phone]"]', '(11) 99999-9999')
                ->type('#modalCobranca input[name="client[email]"]', 'joao@teste.com')
                ->type('#modalCobranca input[name="info_additional"]', 'Teste de cobrança PIX')
                ->pause(1000)
                
                // Submete o formulário
                ->click('#modalCobranca button[type="submit"]')
                ->pause(3000)
                
                // Verifica se foi criado com sucesso - deve abrir o modal do QR Code
                ->waitFor('#modalQrCodePix', 10)
                ->assertVisible('#modalQrCodePix')
                ->assertSee('Pagamento PIX')
                ->assertSee('QR Code')
                ->assertPresent('#qrcode-container img')
                ->assertPresent('#pix-code');
        });
    }

    /**
     * Teste de criação de cobrança cartão de crédito
     */
    public function test_criar_cobranca_cartao_credito_vendedor()
    {
        $user = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/cobranca')
                ->pause(2000)
                ->assertPathIs('/cobranca')
                
                // Clica no botão para abrir o modal
                ->click('button[data-bs-target="#modalCobranca"]')
                ->pause(1000)
                
                // Verifica se o modal está aberto
                ->assertVisible('#modalCobranca')
                
                // Clica na aba de cartão de crédito
                ->click('#cartao-tab')
                ->pause(1000) // Pausa maior para a aba carregar completamente
                ->assertVisible('#cartao-content')
                ->pause(1000) // Pausa adicional para garantir que os campos estejam prontos
                
                // Verifica se a aba de cartão está ativa e os campos estão prontos
                ->assertPresent('#cartao-content input[name="amount"]')
                ->assertPresent('#cartao-content select[name="installments"]')
                ->assertPresent('#cartao-content select[name="interest"]')
                ->pause(1000)
                
                // Tenta interagir diretamente com os campos da aba de cartão
                ->type('#cartao-content input[name="amount"]', '15000') // R$ 150,00
                ->pause(200)
                ->select('#cartao-content select[name="installments"]', '3') // 3x
                ->pause(200)
                ->select('#cartao-content select[name="interest"]', 'CLIENT') // Cliente paga taxas
                ->pause(200)
                
                // Dados do cliente (apenas obrigatórios)
                ->type('#cartao-content input[name="client[first_name]"]', 'Maria')
                ->pause(200)
                ->type('#cartao-content input[name="client[document]"]', '77327746048')
                ->pause(200)
                ->type('#cartao-content input[name="client[phone]"]', '999999999')
                ->pause(200)
                ->type('#cartao-content input[name="client[email]"]', 'maria@teste.com')
                ->pause(200)
                
                // Endereço do cliente (apenas obrigatórios)
                ->type('#cartao-content input[name="client[address][street]"]', 'Rua das Flores')
                ->pause(200)
                ->type('#cartao-content input[name="client[address][number]"]', '123')
                ->pause(200)
                ->type('#cartao-content input[name="client[address][neighborhood]"]', 'Centro')
                ->pause(200)
                ->type('#cartao-content input[name="client[address][zip_code]"]', '01234567')
                ->pause(200)
                ->type('#cartao-content input[name="client[address][city]"]', 'São Paulo')
                ->pause(200)
                ->select('#cartao-content select[name="client[address][state]"]', 'SP')
                ->pause(200)
                
                // Dados do cartão (apenas obrigatórios)
                ->type('#cartao-content input[name="card[holder_name]"]', 'Maria Santos')
                ->pause(200)
                ->type('#cartao-content input[name="card[card_number]"]', '4111111111111111')
                ->pause(200)
                ->select('#cartao-content select[name="card[expiration_month]"]', '12')
                ->pause(200)
                ->select('#cartao-content select[name="card[expiration_year]"]', '2025')
                ->pause(200)
                ->type('#cartao-content input[name="card[security_code]"]', '123')
                ->pause(1000)
                
                // Verifica se o botão de submit está visível e interagível
                ->assertVisible('#cartao-content button[type="submit"]')
                ->assertPresent('#cartao-content button[type="submit"]')
                ->pause(1000)
                
                // Submete o formulário
                ->click('#cartao-content button[type="submit"]')
                ->pause(3000)
                
                // Verifica se foi criado com sucesso (pode ser redirecionamento ou mensagem)
                ->assertPresent('.alert-success, .success-message, .toast-success, .alert-info, .alert-warning');
        });
    }

    /**
     * Teste de criação de boleto
     */
    public function test_criar_boleto_vendedor()
    {
        $user = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/cobranca')
                ->pause(2000)
                ->assertPathIs('/cobranca')
                
                // Clica no botão para abrir o modal
                ->click('button[data-bs-target="#modalCobranca"]')
                ->pause(1000)
                
                // Verifica se o modal está aberto
                ->assertVisible('#modalCobranca')
                
                // Clica na aba de boleto
                ->click('#boleto-tab')
                ->pause(1000) // Pausa para a aba carregar
                ->assertVisible('#boleto-content')
                ->pause(500) // Pausa adicional para garantir que os campos estejam prontos
                
                // Verifica se a aba de boleto está ativa e os campos estão prontos
                ->assertPresent('#boleto-content input[name="amount"]')
                ->assertPresent('#boleto-content input[name="expiration"]')
                ->assertPresent('#boleto-content input[name="payment_limit_date"]')
                ->assertPresent('#boleto-content select[name="recharge"]')
                ->pause(1000)
                
                // Preenche os dados do formulário de boleto
                ->type('#boleto-content input[name="amount"]', '20000') // R$ 200,00
                ->type('#boleto-content input[name="expiration"]', '31/12/2025') // Data de vencimento (padrão BR)
                ->type('#boleto-content input[name="payment_limit_date"]', '15/01/2026') // Data limite (padrão BR)
                ->select('#boleto-content select[name="recharge"]', '0') // Não é recarga
                
                // Dados do cliente
                ->type('#boleto-content input[name="client[first_name]"]', 'Pedro')
                ->type('#boleto-content input[name="client[last_name]"]', 'Oliveira')
                ->type('#boleto-content input[name="client[document]"]', '77327746048')
                ->type('#boleto-content input[name="client[email]"]', 'pedro@teste.com')
                
                // Endereço do cliente
                ->type('#boleto-content input[name="client[address][street]"]', 'Avenida Paulista')
                ->type('#boleto-content input[name="client[address][number]"]', '1000')
                ->type('#boleto-content input[name="client[address][neighborhood]"]', 'Bela Vista')
                ->type('#boleto-content input[name="client[address][zip_code]"]', '01310100')
                ->type('#boleto-content input[name="client[address][city]"]', 'São Paulo')
                ->select('#boleto-content select[name="client[address][state]"]', 'SP')
                ->pause(1000)
                
                // Instruções do Boleto (OBRIGATÓRIO)
                ->select('#boleto-content select[name="instruction[booklet]"]', '0') // Não é carnê
                ->type('#boleto-content input[name="instruction[late_fee][amount]"]', '2,00') // 2% de multa
                ->type('#boleto-content input[name="instruction[interest][amount]"]', '1,00') // 1% de juros
                ->type('#boleto-content input[name="instruction[discount][amount]"]', '5,00') // 5% de desconto
                ->type('#boleto-content input[name="instruction[discount][limit_date]"]', '15/12/2025') // Data limite para desconto
                ->pause(1000)
                
                // Verifica se o botão de submit está visível e interagível
                ->assertVisible('#boleto-content button[type="submit"]')
                ->assertPresent('#boleto-content button[type="submit"]')
                ->pause(1000)
                
                // Submete o formulário
                ->click('#boleto-content button[type="submit"]')
                ->pause(3000)
                
                // Verifica se foi criado com sucesso (pode ser redirecionamento ou mensagem)
                ->assertPresent('.alert-success, .success-message, .toast-success, .alert-info, .alert-warning');
        });
    }

 
    /**
     * Teste de verificação de saldos para admin de loja
     */
    public function test_saldos_admin_loja_vendedor()
    {
        $user = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                ->assertPresent('.saldo-card')
                ->assertPresent('.saldo-disponivel')
                ->assertPresent('.saldo-transito')
                ->assertPresent('.saldo-processamento')
                ->assertPresent('.saldo-bloqueado')
                ->assertPresent('.saldo-bloqueado-boleto');
        });
    }

    /**
     * Teste de verificação de saldos para vendedor de loja (não deve mostrar)
     */
    public function test_saldos_vendedor_loja_vendedor()
    {
        $user = $this->createVendedorLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                ->assertMissing('.saldo-card');
        });
    }

    /**
     * Teste de informações do estabelecimento
     */
    public function test_info_estabelecimento_vendedor()
    {
        $user = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                ->assertPresent('nav[aria-label="breadcrumb"]')
                ->assertSee('Estabelecimento')
                ->assertSee('ID');
        });
    }

    /**
     * Teste de métricas específicas do vendedor
     */
    public function test_metricas_especificas_vendedor()
    {
        $user = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                ->assertPresent('.metric-card')
                ->assertPresent('.metric-value')
                ->assertPresent('.metric-label')
                ->assertPresent('.metric-icon');
        });
    }

    /**
     * Teste de navegação para detalhes de transação
     */
    public function test_navegacao_detalhes_transacao_vendedor()
    {
        $user = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                ->scrollTo('#tabela-transacoes')
                ->pause(1000)
                ->click('#tabela-transacoes .btn-outline-info')
                ->pause(2000)
                ->assertPathIs('/cobranca/transacao/*')
                ->assertPresent('.info-card')
                ->assertPresent('.card-body')
                ->assertSee('Detalhes completos da transação');
        });
    }

    /**
     * Teste de responsividade em dispositivos móveis
     */
    public function test_responsividade_mobile_vendedor()
    {
        $user = $this->createVendedorAdminLoja();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/vendedor/dashboard')
                ->pause(2000)
                ->resize(375, 667) // Redimensiona para mobile
                ->pause(1000)
                ->assertVisible('.navbar-toggler')
                ->click('.navbar-toggler')
                ->pause(500)
                ->assertVisible('.navbar-collapse.show')
                ->resize(1920, 1080) // Volta para o tamanho normal da tela
                ->pause(1000);
        });
    }



 
}



