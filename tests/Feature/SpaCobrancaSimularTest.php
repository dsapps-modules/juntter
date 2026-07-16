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
        $this->assertStringContainsString("return String(flag.name ?? '').toUpperCase() !== 'BACEN';", $pageSource);
        $this->assertStringContainsString("return 'Outros';", $pageSource);
        $this->assertStringContainsString('Bandeira', $pageSource);
        $this->assertStringContainsString('Plano contratado', $pageSource);
        $this->assertStringContainsString('Resultado da simulação', $pageSource);
        $this->assertStringContainsString('Valor de cada parcela', $pageSource);
        $this->assertStringContainsString('spa-sim-result-header', $pageSource);
        $this->assertStringContainsString('spa-sim-result-metrics', $pageSource);
        $this->assertStringContainsString('spa-sim-installment-grid', $pageSource);
        $this->assertStringContainsString('buildInstallmentOptions(selectedFlag)', $pageSource);
        $this->assertStringContainsString('selectedFlagLabel', $pageSource);
        $this->assertStringContainsString('label="Bandeira"', $pageSource);
        $this->assertStringContainsString('options={flagOptions}', $pageSource);
        $this->assertStringContainsString('options={installmentOptions}', $pageSource);
        $this->assertStringContainsString("label = 'Plano considerado'", $planSelectorSource);
        $this->assertStringNotContainsString('Ativa', $planSelectorSource);
        $this->assertStringContainsString('options = []', $planSelectorSource);
        $this->assertStringContainsString('ariaLabel =', $planSelectorSource);
        $this->assertStringContainsString('Quantidade de parcelas', $installmentSelectorSource);
        $this->assertStringContainsString('options = installmentOptions', $installmentSelectorSource);
        $this->assertStringContainsString('ariaLabel =', $installmentSelectorSource);
    }

    public function test_the_shared_payment_simulation_config_contains_the_expected_plan_ranges(): void
    {
        $configSource = file_get_contents(base_path('resources/js/spa/components/payment-simulator/paymentSimulationConfig.js'));

        $this->assertStringContainsString('Plano Acelerar', $configSource);
        $this->assertStringContainsString('Plano Turbo', $configSource);
        $this->assertStringContainsString('Plano Econômico', $configSource);
        $this->assertStringContainsString('2: 6.53', $configSource);
        $this->assertStringContainsString('18: 22.79', $configSource);
        $this->assertStringContainsString('Array.from({ length: 17 }', $configSource);
    }
}
