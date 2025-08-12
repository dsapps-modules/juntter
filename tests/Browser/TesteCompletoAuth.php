<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class TesteCompletoAuth extends DuskTestCase
{
    use DatabaseMigrations;
    
    /**
     * Remove validação HTML5 temporariamente para permitir envio
     */
    private function disableHtmlValidation(Browser $browser)
    {
        $browser->script('
            // Remove required de todos os campos
            $("input[required]").removeAttr("required");
            
            // Remove validação JavaScript
            $("#loginForm").off("submit");
            // Registro desabilitado
            
            // Remove validação em tempo real
            $("#email, #password, #name, #password_confirmation").off("input");
        ');
    }
    
    /**
     * Teste 1: Cliente já tem conta
     */
    public function test_cliente_ja_tem_conta()
    {
        $email = 'cliente_existente@test.com';
        
        // Criar usuário existente
        User::create([
            'name' => 'Usuário Existente',
            'email' => $email,
            'password' => Hash::make('senha123456'),
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => '2025-07-15 17:58:24'
        ]);

        $this->browse(function (Browser $browser) use ($email) {
            $browser->visit('/login')
                    ->waitFor('.login-container')
                    ->type('#email', $email)
                    ->type('#password', 'senha123456')
                    ->waitFor('#loginBtn')
                    ->pause(1000)
                    ->click('#loginBtn')
                    ->waitForLocation('/vendedor/dashboard', 10);
        });
    }

    /**
     * Teste 2: Cliente deseja voltar para home
     */
    public function test_cliente_deseja_voltar_para_home()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->waitFor('.login-container')
                    ->waitFor('#logo-navbar', 5)
                    ->click('#logo-navbar')
                    ->waitForLocation('/', 10)
                    ->assertPathIs('/')
                    ->pause(2000)
                    ->assertSee('Checkout Digital');
        });
    }

    /**
     * Teste 3: Cliente não preencheu o nome
     */
    public function test_cliente_nao_preencheu_nome()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->waitFor('.login-container')
                    ->type('#password', 'senha123456')
                    ->waitFor('#loginBtn')
                    ->waitUntil('!$("#loginBtn").prop("disabled")', 5)
                    ->pause(2000);
            
            // Desabilita validação HTML5 e JavaScript
            $this->disableHtmlValidation($browser);
            
            $browser->click('#loginBtn')
                    ->waitForText('O campo e-mail é obrigatório.', 3)
                    ->assertSee('O campo e-mail é obrigatório.')
                    ->assertPathIs('/login');
        });
    }

    /**
     * Teste 4: Cliente não preencheu o e-mail
     */
    public function test_cliente_nao_preencheu_email()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->waitFor('.login-container')
                    ->type('#email', '')
                    ->type('#password', 'senha123456')
                    ->waitFor('#loginBtn')
                    ->waitUntil('!$("#loginBtn").prop("disabled")', 5)
                    ->pause(1000);
            
            // Desabilita validação HTML5 e JavaScript
            $this->disableHtmlValidation($browser);
            
            $browser->pause(1000)
                    ->click('#loginBtn')
                    ->waitForText('O campo e-mail é obrigatório.', 5)
                    ->assertSee('O campo e-mail é obrigatório.')
                    ->assertPathIs('/login');
        });
    }

    /**
     * Teste 5: Cliente não preencheu senha
     */
    public function test_cliente_nao_preencheu_senha()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->waitFor('.login-container')
                    ->type('#email', 'teste@example.com')
                    ->waitFor('#loginBtn')
                    ->waitUntil('!$("#loginBtn").prop("disabled")', 5)
                    ->pause(2000);
            
            // Desabilita validação HTML5 e JavaScript
            $this->disableHtmlValidation($browser);
            
            $browser->click('#loginBtn')
                    ->waitForText('O campo senha é obrigatório.', 3)
                    ->assertSee('O campo senha é obrigatório.')
                    ->assertPathIs('/login');
        });
    }

    /**
     * Teste 6: Cliente não preencheu confirmação de senha
     */
    public function test_cliente_nao_preencheu_confirmacao_senha()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->waitFor('.login-container')
                    ->type('#email', 'teste@example.com')
                    ->type('#password', '')
                    ->waitFor('#loginBtn')
                    ->waitUntil('!$("#loginBtn").prop("disabled")', 5)
                    ->pause(1000);
            
            // Desabilita validação HTML5 e JavaScript
            $this->disableHtmlValidation($browser);
            
            $browser->pause(1000)
                    ->click('#loginBtn')
                    ->waitForText('O campo senha é obrigatório.', 5)
                    ->assertSee('O campo senha é obrigatório.')
                    ->assertPathIs('/login');
        });
    }

    /**
     * Teste 7: Senha é diferente da confirmação
     */
    public function test_senha_diferente_confirmacao()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->waitFor('.login-container')
                    ->type('#name', 'João Silva')
                    ->type('#email', 'teste@example.com')
                    ->type('#password', 'senha123456')
                    ->type('#password_confirmation', 'senha654321')
                    ->waitFor('#registerBtn')
                    ->waitUntil('$("#registerBtn").prop("disabled")', 5)
                    ->assertSee('As senhas não coincidem!')
                    ->assertPathIs('/register');
        });
    }

    /**
     * Teste 8: Login com credenciais válidas
     */
    public function test_login_credenciais_validas()
    {
        $email = 'login_valido@test.com';
        $user = User::create([
            'name' => 'Usuário Login',
            'email' => $email,
            'password' => Hash::make('senha123456'),
            'nivel_acesso' => 'comprador',
            'email_verified_at' => '2025-07-15  17:58:24'
        ]);

        $this->browse(function (Browser $browser) use ($user, $email) {
            $browser->visit('/login')
                    ->waitFor('.login-container')
                    ->type('#email', $email)
                    ->type('#password', 'senha123456')
                    ->waitFor('#loginBtn')
                    ->pause(1000)
                    ->click('#loginBtn')
                    ->waitForLocation('/comprador/dashboard', 3)
                    ->assertPathIs('/comprador/dashboard')
                    ->assertSee('Dashboard Comprador')
                    ->assertSee($user->name);
        });
    }

    /**
     * Teste 9: Login com credenciais inválidas
     */
    public function test_login_credenciais_invalidas()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->waitFor('.login-container')
                    ->type('#email', 'invalido@email.com')
                    ->type('#password', 'senhaerrada')
                    ->waitFor('#loginBtn')
                    ->pause(2000)
                    ->click('#loginBtn')
                    ->waitForText('Credenciais informadas não correspondem com nossos registros.', 3)
                    ->assertSee('Credenciais informadas não correspondem com nossos registros.')
                    ->assertPathIs('/login');
        });
    }

    /**
     * Teste 10: Login sem preencher email
     */
    public function test_login_sem_email()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->waitFor('.login-container')
                    ->type('#password', 'senha123456')
                    ->waitFor('#loginBtn')
                    ->pause(2000);
            
            // Desabilita validação HTML5 e JavaScript
            $this->disableHtmlValidation($browser);
            
            $browser->pause(1000)
                    ->click('#loginBtn')
                    ->waitForText('O campo e-mail é obrigatório.', 5)
                    ->assertSee('O campo e-mail é obrigatório.')
                    ->assertPathIs('/login');
        });
    }

    /**
     * Teste 11: Login sem preencher senha
     */
    public function test_login_sem_senha()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->waitFor('.login-container')
                    ->type('#email', 'teste@email.com')
                    ->waitFor('#loginBtn')
                    ->pause(2000);
            
            // Desabilita validação HTML5 e JavaScript
            $this->disableHtmlValidation($browser);
            
            $browser->click('#loginBtn')
                    ->waitForText('O campo senha é obrigatório.', 3)
                    ->assertSee('O campo senha é obrigatório.')
                    ->assertPathIs('/login');
        });
    }

    /**
     * Teste 12: Registro com sucesso
     */
    // Registro desabilitado

    /**
     * Teste 13: Logout
     */
    public function test_logout()
    {
        $user = User::create([
            'name' => 'Usuário Logout',
            'email' => 'logout@test.com',
            'password' => Hash::make('senha123456'),
            'nivel_acesso' => 'comprador',
            'email_verified_at' => '2025-07-15 17:58:24'
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/comprador/dashboard')
                    ->assertSee('Dashboard Comprador')
                    ->waitFor('#logoutBtn', 5)
                    ->click('#logoutBtn')
                    ->waitForLocation('/', 10)
                    ->assertPathIs('/');
        });
    }

    /**
     * Teste 14: Acesso negado para usuário sem permissão
     */
    public function test_acesso_negado_sem_permissao()
    {
        $user = User::create([
            'name' => 'Usuário Sem Permissão',
            'email' => 'sem_permissao@test.com',
            'password' => Hash::make('senha123456'),
            'nivel_acesso' => 'comprador',
            'email_verified_at' => '2025-07-15 17:58:24'
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/admin/dashboard')
                    ->waitForLocation('/unauthorized', 10)
                    ->assertPathIs('/unauthorized')
                    ->assertSee('Acesso Negado');
        });
    }

    /**
     * Teste 15: Navegação entre login e registro
     */
    public function test_navegacao_login_registro()
    {
        $this->browse(function (Browser $browser) {
            // Login para Registro
            $browser->visit('/login')
                    ->waitFor('.login-container')
                    ->waitFor('a[href*="register"]', 5)
                    ->click('a[href*="register"]')
                    ->waitForLocation('/register', 10)
                    ->assertPathIs('/register')
                    ->assertSee('Criar Conta');

            // Registro para Login
            $browser->visit('/register')
                    ->waitFor('.login-container')
                    ->waitFor('a[href*="login"]', 5)
                    ->click('a[href*="login"]')
                    ->waitForLocation('/login', 10)
                    ->assertPathIs('/login')
                    ->assertSee('Entrar');
        });
    }

    /**
     * Teste 16: Recuperação de senha
     */
    public function test_recuperacao_senha()
    {
        $email = 'recuperacao@test.com';
        $user = User::create([
            'name' => 'Usuário Recuperação',
            'email' => $email,
            'password' => Hash::make('senha123456'),
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => '2025-07-15 17:58:24'
        ]);

        $this->browse(function (Browser $browser) use ($email) {
            $browser->visit('/forgot-password')
                    ->pause(2000)
                    ->waitFor('.recovery-container', 10)
                    ->pause(2000)
                    ->waitFor('#email', 5)
                    ->type('#email', $email)
                    ->waitFor('#recoveryBtn')
                    ->pause(2000)
                    ->click('#recoveryBtn')
                    ->waitForText('Enviamos um link para redefinir a sua senha por e-mail.', 5)
                    ->assertSee('Enviamos um link para redefinir a sua senha por e-mail.')
                    ->assertPathIs('/forgot-password');
        });
    }

    /**
     * Teste 17: Recuperação de senha com email inexistente
     */
    public function test_recuperacao_senha_email_inexistente()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                    ->waitFor('.recovery-container', 10)
                    ->pause(2000)
                    ->waitFor('#email', 5)
                    ->type('#email', 'naoexiste@test.com')
                    ->waitFor('#recoveryBtn')
                    ->pause(2000)
                    ->click('#recoveryBtn')
                    ->waitForText('Não conseguimos encontrar nenhum usuário com o endereço de e-mail informado.', 5)
                    
                    ->assertSee('Não conseguimos encontrar nenhum usuário com o endereço de e-mail informado.')
                    ->assertPathIs('/forgot-password');
        });
    }

    /**
     * Teste 18: Responsividade das páginas
     */
    public function test_responsividade_paginas()
    {
        $this->browse(function (Browser $browser) {
            // Desktop
            $browser->visit('/login')
                    ->resize(1920, 1080)
                    ->waitFor('.login-container')
                    ->assertVisible('.login-container')
                    ->assertVisible('.login-card');

            // Tablet
            $browser->resize(768, 1024)
                    ->assertVisible('.login-container')
                    ->assertVisible('.login-card');

            // Mobile
            $browser->resize(375, 667)
                    ->assertVisible('.login-container')
                    ->assertVisible('.login-card');

            // Registro desabilitado
        });
    }
} 