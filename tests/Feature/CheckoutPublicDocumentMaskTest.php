<?php

namespace Tests\Feature;

use Tests\TestCase;

class CheckoutPublicDocumentMaskTest extends TestCase
{
    public function test_card_holder_document_field_uses_the_shared_cpf_cnpj_mask(): void
    {
        $componentSource = file_get_contents(base_path('resources/js/checkout-spa.jsx'));

        $this->assertIsString($componentSource);
        $this->assertStringContainsString('name="card[holder_document]"', $componentSource);
        $this->assertStringContainsString('placeholder="CPF/CNPJ"', $componentSource);
        $this->assertStringContainsString('onInput={handleDocumentMask}', $componentSource);
        $this->assertStringContainsString("target.name === 'card[holder_document]'", $componentSource);
        $this->assertStringContainsString('formatDocument', $componentSource);
    }

    public function test_checkout_spa_view_exposes_the_spa_shell_and_asset_hooks(): void
    {
        $viewSource = file_get_contents(base_path('resources/views/checkout/spa.blade.php'));

        $this->assertIsString($viewSource);
        $this->assertStringContainsString('checkout-spa-data', $viewSource);
        $this->assertStringContainsString('checkout-spa-root', $viewSource);
        $this->assertStringContainsString('checkoutSpaAssets', $viewSource);
    }
}
