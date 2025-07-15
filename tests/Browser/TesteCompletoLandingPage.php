<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;

class TesteCompletoLandingPage extends DuskTestCase
{
    /**
     * Teste de carregamento da página inicial
     */
    public function test_pagina_inicial_carrega()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->waitUntilMissing('#loading', 5)
                    ->waitFor('.hero-section', 10)
                    ->waitForText('Checkout Digital que', 10)
                    ->waitForText('vende por você', 10)
                    ->waitForText('Criar Meu Checkout', 10)
                    ->assertSee('Checkout Digital que')
                    ->assertSee('vende por você')
                    ->assertSee('Criar Meu Checkout');
        });
    }

    /**
     * Teste de navegação pela navbar - Benefícios
     */
    public function test_navegacao_beneficios()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->waitUntilMissing('#loading', 5)
                    ->waitFor('.hero-section', 10)
                    ->click('#beneficioslink')
                    ->pause(2000)
                    ->assertSee('Por que usar o Juntter Checkout?');
        });
    }

    /**
     * Teste de navegação pela navbar - Preços
     */
    public function test_navegacao_precos()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->waitUntilMissing('#loading', 5)
                    ->waitFor('.hero-section', 10)
                    ->click('#precoslink')
                    ->pause(2000)
                    ->assertSee('Planos transparentes');
        });
    }

    /**
     * Teste de navegação pela navbar - Como Funciona
     */
    public function test_navegacao_como_funciona()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->waitUntilMissing('#loading', 5)
                    ->waitFor('.hero-section', 10)
                    ->click('#como-funcionalink')
                    ->pause(2000)
                    ->assertSee('Como funciona?')
                    ->assertSee('Venda online em 3 passos simples');
        });
    }

    /**
     * Teste de navegação pela navbar - Depoimentos
     */
    public function test_navegacao_depoimentos()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->waitUntilMissing('#loading', 5)
                    ->waitFor('.hero-section', 10)
                    ->click('#depoimentoslink')
                    ->pause(2000)
                    ->waitFor('#depoimentos', 10)
                    ->assertSee('O que nossos clientes dizem');
        });
    }

    /**
     * Teste de navegação pela navbar - FAQ
     */
    public function test_navegacao_faq()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->waitUntilMissing('#loading', 5)
                    ->waitFor('.hero-section', 10)
                    ->click('#faqlink')
                    ->pause(2000)
                    ->waitFor('#faq', 10)
                    ->assertSee('Perguntas Frequentes');
        });
    }

    /**
     * Teste de clique no botão CTA principal
     */
    public function test_clique_botao_cta_principal()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->waitUntilMissing('#loading', 5)
                    ->waitFor('.hero-section', 10)
                    ->click('.btn-hero')
                    ->waitForLocation('/register', 10)
                    ->assertPathIs('/register');
        });
    }

    /**
     * Teste de clique nos botões dos planos
     */
    public function test_clique_botoes_planos()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->waitUntilMissing('#loading', 5)
                    ->waitFor('.hero-section', 10)
                    ->scrollTo('#precos')
                    ->pause(2000)
                    ->waitFor('#precos', 10);

            // Teste botão Starter (primeiro botão outline-warning)
            $browser->click('#starter')
                    ->waitForLocation('/register', 10)
                    ->assertPathIs('/register');

            // Teste botão Pro
            $browser->visit('/')
                    ->waitUntilMissing('#loading', 5)
                    ->waitFor('.hero-section', 10)
                    ->scrollTo('#precos')
                    ->pause(2000)
                    ->waitFor('#precos', 10)
                    ->click('#pro')
                    ->waitForLocation('/register', 10)
                    ->assertPathIs('/register');

            // Teste botão Enterprise
            $browser->visit('/')
                    ->waitUntilMissing('#loading', 5)
                    ->waitFor('.hero-section', 10)
                    ->scrollTo('#precos')
                    ->pause(2000)
                    ->waitFor('#precos', 10)
                    ->click('#enterprise')
                    ->waitForLocation('/register', 10)
                    ->assertPathIs('/register');
        });
    }

    /**
     * Teste de interação com FAQ
     */
    public function test_interacao_faq()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->waitUntilMissing('#loading', 5)
                    ->waitFor('.hero-section', 10)
                    ->scrollTo('#faq')
                    ->pause(2000)
                    ->click('.faq-question')
                    ->waitFor('.faq-answer', 5)
                    ->assertVisible('.faq-answer');
        });
    }

    /**
     * Teste de responsividade da navbar
     */
    public function test_navbar_responsiva()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->waitUntilMissing('#loading', 5)
                    ->waitFor('.hero-section', 10);

            // Teste em desktop
            $browser->assertVisible('.navbar-nav')
                    ->assertSee('Benefícios')
                    ->assertSee('Preços')
                    ->assertSee('Como Funciona')
                    ->assertSee('Depoimentos')
                    ->assertSee('FAQ');

            // Teste em mobile
            $browser->resize(375, 667)
                    ->pause(1000)
                    ->assertVisible('.navbar-toggler')
                    ->click('.navbar-toggler')
                    ->pause(500)
                    ->assertVisible('.navbar-collapse.show');
        });
    }

    /**
     * Teste de links da navbar para páginas de auth
     */
    public function test_links_navbar_auth()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(1920, 1080)
                    ->visit('/')
                    ->waitUntilMissing('#loading', 5)
                    ->waitFor('.hero-section', 10);

            // Teste link Login
            $browser->waitFor('#login-navbar', 10)
                    ->click('#login-navbar')
                    ->waitForLocation('/login', 10)
                    ->assertPathIs('/login');

            // Volta para home
            $browser->visit('/')
                    ->waitUntilMissing('#loading', 5)
                    ->waitFor('.hero-section', 10);

            // Teste link Criar Conta
            $browser->waitFor('#criarconta-navbar', 10)
                    ->click('#criarconta-navbar')
                    ->waitForLocation('/register', 10)
                    ->assertPathIs('/register');
        });
    }

    public function test_botao_comecar_gratis()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->waitUntilMissing('#loading', 5)
                    ->waitFor('.hero-section', 10)
                    ->scrollTo('#comecar-agora')
                    ->pause(2000)
                    ->click('#comecar-agora')
                    ->waitForLocation('/register', 10)
                    ->assertPathIs('/register');
        });
    }

    
 


    public function test_footer_navegacao_beneficios()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->waitUntilMissing('#loading', 5)
                    ->waitFor('.hero-section', 10)
                    ->scrollTo('#footer')
                    ->pause(2000)
                    ->click('#beneficiosfooter')
                    ->pause(2000)
                    ->assertSee('Por que usar o Juntter Checkout?');
                    
                    
        });
      }

      public function test_footer_navegacao_precos()
      {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->waitUntilMissing('#loading', 5)
                    ->waitFor('.hero-section', 10)
                    ->scrollTo('#footer') 
                    ->pause(2000)
                    ->click('#precosfooter')
                    ->pause(2000)
                    ->assertSee('Planos transparentes');
                    
        });
      }

      public function test_footer_navegacao_como_funciona()
      {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->waitUntilMissing('#loading', 5)
                    ->waitFor('.hero-section', 10)
                    ->scrollTo('#footer')
                    ->pause(2000)
                    ->click('#como-funcionafooter')
                    ->pause(2000)
                    ->assertSee('Como funciona?');
        });
      }
      
      
      
}
