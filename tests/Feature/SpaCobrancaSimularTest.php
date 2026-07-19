<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class SpaCobrancaSimularTest extends TestCase
{
    use DatabaseMigrations;

    public function test_the_simular_route_returns_the_spa_shell(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/app/cobranca/simular');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_simular_page_contains_the_new_simulation_controls(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/cobranca/CobrancaSimularPage.jsx'));
        $planSelectorSource = file_get_contents(base_path('resources/js/spa/components/payment-simulator/PaymentPlanSelector.jsx'));
        $installmentSelectorSource = file_get_contents(base_path('resources/js/spa/components/payment-simulator/PaymentInstallmentSelector.jsx'));

        $this->assertStringContainsString("fetch('/api/spa/cobranca/planos'", $pageSource);
        $this->assertStringContainsString('contractedFlags = data.plan?.flags ?? []', $pageSource);
        $this->assertStringContainsString('formatFlagLabel(selectedFlag)', $pageSource);
        $this->assertStringContainsString('resolvePixRate(bacenFlag)', $pageSource);
        $this->assertStringContainsString('paymentMethod', $pageSource);
        $this->assertStringContainsString('flag?.fees?.pix', $pageSource);
        $this->assertStringContainsString("String(flag?.name ?? '').toUpperCase() === 'BACEN'", $pageSource);
        $this->assertStringContainsString('spa-sim-method-toggle', $pageSource);
        $this->assertStringContainsString('Simular recebimento por PIX', $pageSource);
        $this->assertStringContainsString('Bandeira', $pageSource);
        $this->assertStringContainsString('title={planName}', $pageSource);
        $this->assertStringContainsString('Quem paga a taxa:', $pageSource);
        $this->assertStringContainsString('Radio.Group', $pageSource);
        $this->assertStringContainsString('Cliente', $pageSource);
        $this->assertStringContainsString('Vendedor', $pageSource);
        $this->assertStringContainsString('spa-sim-result-header', $pageSource);
        $this->assertStringContainsString('spa-sim-result-metrics', $pageSource);
        $this->assertStringContainsString('/img/payment/logo-pix.png', $pageSource);
        $this->assertStringContainsString('resolveRate(selectedFlag, installments)', $pageSource);
        $this->assertStringContainsString('buildInstallmentOptions(selectedFlag)', $pageSource);
        $this->assertStringContainsString('selectedFlagLabel', $pageSource);
        $this->assertStringContainsString('label="Bandeira"', $pageSource);
        $this->assertStringContainsString('options={flagOptions}', $pageSource);
        $this->assertStringContainsString('options={installmentOptions}', $pageSource);
        $this->assertStringContainsString("label = 'Plano considerado'", $planSelectorSource);
        $this->assertStringNotContainsString('Ativa', $planSelectorSource);
        $this->assertStringContainsString('options = []', $planSelectorSource);
        $this->assertStringContainsString('ariaLabel =', $planSelectorSource);
        $this->assertStringContainsString('Parcelas', $installmentSelectorSource);
        $this->assertStringContainsString('options = installmentOptions', $installmentSelectorSource);
        $this->assertStringContainsString('ariaLabel =', $installmentSelectorSource);
    }

    public function test_the_shared_payment_simulation_config_is_now_snapshot_driven(): void
    {
        $configSource = file_get_contents(base_path('resources/js/spa/components/payment-simulator/paymentSimulationConfig.js'));

        $this->assertStringContainsString('normalizeFlags(flags)', $configSource);
        $this->assertStringContainsString('formatFlagLabel(flag)', $configSource);
        $this->assertStringContainsString('buildInstallmentOptions(flag)', $configSource);
        $this->assertStringContainsString('resolveRate(flag, installmentValue)', $configSource);
        $this->assertStringContainsString('Array.from({ length: 17 }', $configSource);
        $this->assertStringNotContainsString('Plano Acelerar', $configSource);
        $this->assertStringNotContainsString('Plano Turbo', $configSource);
        $this->assertStringNotContainsString('Plano Economico', $configSource);
    }
}
