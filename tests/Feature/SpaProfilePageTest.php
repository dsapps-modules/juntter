<?php

namespace Tests\Feature;

use Tests\TestCase;

class SpaProfilePageTest extends TestCase
{
    public function test_profile_navigation_item_uses_the_profile_label(): void
    {
        $navigationSource = file_get_contents(base_path('resources/js/spa/navigation/menu.js'));

        $this->assertStringContainsString("label: 'Perfil'", $navigationSource);
        $this->assertStringNotContainsString('Configura', $navigationSource);
    }

    public function test_profile_page_exposes_profile_and_password_forms(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/ProfilePage.jsx'));
        $appSource = file_get_contents(base_path('resources/js/spa/App.jsx'));

        $this->assertStringContainsString('className="spa-hero-card"', $pageSource);
        $this->assertStringContainsString("submitJson('/profile', 'PATCH', profileForm)", $pageSource);
        $this->assertStringContainsString("submitJson('/password', 'PUT', passwordForm)", $pageSource);
        $this->assertStringContainsString('Dados pessoais', $pageSource);
        $this->assertStringContainsString('Alterar senha', $pageSource);
        $this->assertStringContainsString('Conta criada:', $pageSource);
        $this->assertStringContainsString('Resumo da conta', $pageSource);
        $this->assertStringNotContainsString('A senha atual é necessária para confirmar a alteração.', $pageSource);
        $this->assertStringNotContainsString('spa-mini-surface', $pageSource);
        $this->assertStringNotContainsString('/change-password', $pageSource);
        $this->assertStringNotContainsString('Fluxo obrigatório', $pageSource);
        $this->assertStringNotContainsString('Fluxo de senha obrigatória', $pageSource);
        $this->assertStringNotContainsString('/change-password', $appSource);
    }
}
