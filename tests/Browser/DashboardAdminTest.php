<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Vendedor;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DashboardAdminTest extends DuskTestCase
{
    use DatabaseMigrations;

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => Hash::make('senha123456'),
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

 

    /**
     * Teste básico do dashboard admin
     */
    public function test_dashboard_admin_basico()
    {
        $user = $this->createAdmin();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/dashboard')
                ->pause(2000)
                ->assertPathIs('/admin/dashboard')
                ->screenshot('admin_dashboard_basico')
                ->assertSee('Admin')
                ->assertPresent('.saldo-card')
                ->assertPresent('.analytics-tabs')
                ->assertPresent('#estabelecimentos-table');
        });
    }

  

    /**
     * Teste das abas de analytics
     */
    public function test_abas_analytics_admin()
    {
        $user = $this->createAdmin();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/dashboard')
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
     * Teste da tabela de estabelecimentos
     */
    public function test_tabela_estabelecimentos_admin()
    {
        $user = $this->createAdmin();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/dashboard')
                ->pause(1000)
                ->scrollTo('#estabelecimentos-table')
                ->pause(1000)

                ->assertPresent('#estabelecimentos-table')
                ->assertPresent('#estabelecimentos-table thead')
                ->assertPresent('#estabelecimentos-table tbody');
               
        });
    }

    /**
     * Teste dos botões de ação da tabela - Visualizar
     */
    public function test_botao_visualizar_estabelecimento_admin()
    {
        $user = $this->createAdmin();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/dashboard')
                ->pause(2000)
                ->scrollTo('#estabelecimentos-table')
                ->pause(1000)

                // Verifica se existe pelo menos um botão de visualizar
                ->assertPresent('#estabelecimentos-table .btn-outline-info')
                ->assertPresent('#estabelecimentos-table .fa-eye')

                ->click('#estabelecimentos-table .btn-outline-info')
                ->pause(2000)

                // Verifica se foi redirecionado para a página de detalhes
                ->assertPathIs('/estabelecimentos/*')
                ->screenshot('admin_estabelecimento_visualizar');
        });
    }

    /**
     * Teste dos botões de ação da tabela - Editar
     */
    public function test_botao_editar_estabelecimento_admin()
    {
        $user = $this->createAdmin();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/dashboard')
                ->pause(2000)
                ->scrollTo('#estabelecimentos-table')
                ->pause(1000)

                // Verifica se existe pelo menos um botão de editar
                ->assertPresent('#estabelecimentos-table .btn-outline-warning')
                ->assertPresent('#estabelecimentos-table .fa-edit')

         
                ->click('#estabelecimentos-table .btn-outline-warning')
                ->pause(2000)

                // Verifica se foi redirecionado para a página de edição
                ->assertPathIs('/estabelecimentos/*/edit')
                ->screenshot('admin_estabelecimento_editar');
        });
    }

    /**
     * Teste de presença dos botões de ação na tabela
     */
    public function test_botoes_acao_tabela_admin()
    {
        $user = $this->createAdmin();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/dashboard')
                ->pause(2000)
                ->scrollTo('#estabelecimentos-table')
                ->pause(1000)

                // Verifica se a tabela existe e tem conteúdo
                ->assertPresent('#estabelecimentos-table')
                ->assertPresent('#estabelecimentos-table tbody')
                
                // Verifica se existe pelo menos uma linha na tabela
                ->assertPresent('#estabelecimentos-table tbody tr')

                // Verifica se os botões estão presentes 
                ->assertPresent('#estabelecimentos-table .btn-group')
                ->assertPresent('#estabelecimentos-table .btn-outline-info')
                ->assertPresent('#estabelecimentos-table .btn-outline-warning')
                ->assertPresent('#estabelecimentos-table .fa-eye')
                ->assertPresent('#estabelecimentos-table .fa-edit')

                // Verifica se os botões têm os títulos corretos 
                ->assertAttribute('#estabelecimentos-table .btn-outline-info', 'title', 'Visualizar')
                ->assertAttribute('#estabelecimentos-table .btn-outline-warning', 'title', 'Editar');
        });
    }

    /**
     * Teste do breadcrumb
     */
    public function test_breadcrumb_admin()
    {
        $user = $this->createAdmin();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/dashboard')
                ->pause(2000)
                ->assertPresent('nav[aria-label="breadcrumb"]')
                ->assertPresent('nav[aria-label="breadcrumb"] a[href*="dashboard"]')
                ->assertSee('Dashboard')
                ->assertSee('Administração');
        });
    }

    /**
     * Teste do menu do usuário
     */
    public function test_menu_usuario_admin()
    {
        $user = $this->createAdmin();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/dashboard')
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
    public function test_navegacao_editar_perfil_admin()
    {
        $user = $this->createAdmin();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/dashboard')
                ->pause(2000)
                ->click('#userDropdown')
                ->pause(500)
                ->click('#editar-perfil')
                ->waitForLocation('/profile', 10)
                ->assertPathIs('/profile');
        });
    }

    /**
     * Teste de navegação para alterar senha
     */
    public function test_navegacao_alterar_senha_admin()
    {
        $user = $this->createAdmin();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/dashboard')
                ->pause(2000)
                ->click('#userDropdown')
                ->pause(500)
                ->click('#alterar-senha')
                ->waitForLocation('/profile/password', 10)
                ->assertPathIs('/profile/password');
        });
    }

    /**
     * Teste de logout
     */
    public function test_logout_admin()
    {
        $user = $this->createAdmin();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/dashboard')
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
    public function test_navbar_responsiva_admin()
    {
        $user = $this->createAdmin();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/dashboard')
                ->pause(2000)
                ->resize(375, 667)
                ->pause(1000)
                ->assertVisible('.navbar-toggler')
                ->click('.navbar-toggler')
                ->pause(500)
                ->assertVisible('.navbar-collapse.show');
        });
    }

    /**
     * Teste de métricas visíveis
     */
    public function test_metricas_visiveis_admin()
    {
        $user = $this->createAdmin();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/dashboard')
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
    public function test_navegacao_breadcrumb_admin()
    {
        $user = $this->createAdmin();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/dashboard')
                ->pause(2000)
                ->click('nav[aria-label="breadcrumb"] a[href*="dashboard"]')
                ->waitForLocation('/admin/dashboard', 10)
                ->assertPathIs('/admin/dashboard');
        });
    }

    /**
     * Teste de carregamento completo da página
     */
    public function test_carregamento_completo_admin()
    {
        $user = $this->createAdmin();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/dashboard')
                ->pause(2000)
                ->assertPresent('.dashboard-content')
                ->assertPresent('.container')
                ->assertPresent('.navbar-brand')
                ->assertPresent('.logo-img')
                ->assertPresent('.dashboard-navbar')
                ->assertPresent('.saldo-card')
                ->assertPresent('.analytics-card')
                ->assertPresent('.card')
                ->assertPresent('.table-responsive');
        });
    }
}


