<?php

namespace Tests\Feature;

use Tests\TestCase;

class CheckoutPublicDocumentMaskTest extends TestCase
{
    public function test_card_holder_document_field_uses_the_shared_cpf_cnpj_mask(): void
    {
        $componentSource = file_get_contents(base_path('resources/js/checkout-public.js'));

        $this->assertIsString($componentSource);
        $this->assertStringContainsString("fillInput('[name=\"card[holder_document]\"]', session.customer_document);", $componentSource);
        $this->assertStringContainsString("if (fieldName === 'card[holder_document]') {", $componentSource);
        $this->assertStringContainsString('element.value = formatDocument(value);', $componentSource);
        $this->assertStringContainsString('function applyPaymentMask(target)', $componentSource);
        $this->assertStringContainsString("target.name === 'card[holder_document]'", $componentSource);
        $this->assertStringContainsString("setFieldErrors({ 'card.holder_document': [] });", $componentSource);
    }

    public function test_checkout_view_exposes_a_cpf_cnpj_placeholder_for_the_card_holder_document_field(): void
    {
        $viewSource = file_get_contents(base_path('resources/views/checkout/public.blade.php'));

        $this->assertIsString($viewSource);
        $this->assertStringContainsString('name="card[holder_document]"', $viewSource);
        $this->assertStringContainsString('placeholder="CPF/CNPJ"', $viewSource);
        $this->assertStringContainsString('maxlength="18"', $viewSource);
        $this->assertStringContainsString('inputmode="numeric"', $viewSource);
    }
}
