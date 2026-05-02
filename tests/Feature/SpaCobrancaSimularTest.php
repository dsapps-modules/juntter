<?php

namespace Tests\Feature;

use Tests\TestCase;

class SpaCobrancaSimularTest extends TestCase
{
    public function test_the_simular_route_returns_the_spa_shell(): void
    {
        $response = $this->get('/app/cobranca/simular');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_simular_page_contains_the_new_simulation_controls(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/cobranca/CobrancaSimularPage.jsx'));
        $amountFieldSource = file_get_contents(base_path('resources/js/spa/components/payment-simulator/PaymentAmountField.jsx'));
        $planSelectorSource = file_get_contents(base_path('resources/js/spa/components/payment-simulator/PaymentPlanSelector.jsx'));
        $installmentSelectorSource = file_get_contents(base_path('resources/js/spa/components/payment-simulator/PaymentInstallmentSelector.jsx'));

        $this->assertStringNotContainsString('Monte a simulação em tempo real', $pageSource);
        $this->assertStringNotContainsString('Simular Transação', $pageSource);
        $this->assertStringNotContainsString('Calculadora de taxas', $pageSource);
        $this->assertStringNotContainsString('Breadcrumb', $pageSource);
        $this->assertStringContainsString('Valor', $pageSource);
        $this->assertStringContainsString('Resultado da simulação', $pageSource);
        $this->assertStringContainsString('Valor de cada parcela', $pageSource);
        $this->assertStringContainsString('label="Taxa"', $pageSource);
        $this->assertStringContainsString('label="Parcela"', $pageSource);
        $this->assertStringContainsString('label="Total"', $pageSource);
        $this->assertStringContainsString('PaymentPlanSelector', $pageSource);
        $this->assertStringContainsString('PaymentAmountField', $pageSource);
        $this->assertStringContainsString('PaymentInstallmentSelector', $pageSource);
        $this->assertStringContainsString('buildInstallmentBreakdown(totalAmount, installments)', $pageSource);
        $this->assertStringNotContainsString('spa-metric-tile', $pageSource);
        $this->assertStringNotContainsString('Parcela estimada', $pageSource);
        $this->assertStringNotContainsString('Taxa aplicada', $pageSource);
        $this->assertStringNotContainsString('Taxa estimada', $pageSource);
        $this->assertStringNotContainsString('Total com taxa', $pageSource);
        $this->assertStringNotContainsString('Valor da compra', $pageSource);
        $this->assertStringNotContainsString('feeAmount', $pageSource);
        $this->assertStringNotContainsString('A taxa exibida abaixo usa a combinação do plano selecionado com a quantidade de parcelas.', $pageSource);
        $this->assertStringContainsString('Plano considerado', $planSelectorSource);
        $this->assertStringContainsString('Valor', $amountFieldSource);
        $this->assertStringContainsString("align = 'left'", $amountFieldSource);
        $this->assertStringContainsString("justifyContent: 'space-between'", $amountFieldSource);
        $this->assertStringContainsString('align="right"', $pageSource);
        $this->assertStringContainsString("align = 'left'", $planSelectorSource);
        $this->assertStringContainsString("justifyContent: 'space-between'", $planSelectorSource);
        $this->assertStringContainsString('align="right"', $pageSource);
        $this->assertStringContainsString('Quantidade de parcelas', $installmentSelectorSource);
        $this->assertStringContainsString("align = 'left'", $installmentSelectorSource);
        $this->assertStringContainsString("justifyContent: 'space-between'", $installmentSelectorSource);
        $this->assertStringContainsString("flex: isRightAligned ? '0 0 260px' : '1 1 auto'", $installmentSelectorSource);
        $this->assertStringContainsString('align="right"', $pageSource);
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
