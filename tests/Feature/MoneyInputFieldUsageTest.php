<?php

namespace Tests\Feature;

use Tests\TestCase;

class MoneyInputFieldUsageTest extends TestCase
{
    public function test_target_pages_reuse_the_shared_money_input_component(): void
    {
        $assertions = [
            base_path('resources/js/spa/components/payment-simulator/PaymentAmountField.jsx') => [
                'import MoneyInputField',
                '<MoneyInputField',
            ],
            base_path('resources/js/spa/pages/cobranca/CobrancaSimularPage.jsx') => [
                'PaymentAmountField',
            ],
            base_path('resources/js/spa/pages/cobranca/CobrancaPixPage.jsx') => [
                'MoneyInputField size="large" placeholder="0,00"',
                'MoneyInputField size="large" placeholder="R$ 5,55"',
            ],
            base_path('resources/js/spa/pages/cobranca/CobrancaCartaoCreditoPage.jsx') => [
                'MoneyInputField size="large" placeholder="0,00"',
                'MoneyInputField size="large" placeholder="R$ 25,00"',
            ],
            base_path('resources/js/spa/pages/cobranca/CobrancaBoletoPage.jsx') => [
                'MoneyInputField size="large" placeholder="0,00"',
            ],
            base_path('resources/js/spa/pages/checkout/CheckoutLinkFormPage.jsx') => [
                'MoneyInputField className="w-full"',
                'unit_price: parseCurrencyInput(values.unit_price)',
            ],
        ];

        foreach ($assertions as $path => $needles) {
            $source = file_get_contents($path);

            $this->assertIsString($source, $path);

            foreach ($needles as $needle) {
                $this->assertStringContainsString($needle, $source, $path.' should contain '.$needle);
            }
        }
    }
}
