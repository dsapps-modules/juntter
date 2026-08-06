<?php

namespace Tests\Feature;

use Tests\TestCase;

class PagamentoClienteDocumentMaskTest extends TestCase
{
    public function test_card_holder_document_field_uses_a_dynamic_cpf_cnpj_mask(): void
    {
        $viewSource = file_get_contents(base_path('resources/views/components/form/pagina-pagamento-dados-cartao.blade.php'));
        $scriptSource = file_get_contents(base_path('public/js/checkout-scripts.js'));

        $this->assertIsString($viewSource);
        $this->assertIsString($scriptSource);

        $this->assertStringContainsString('name="client[document]"', $viewSource);
        $this->assertStringContainsString('placeholder="CPF/CNPJ"', $viewSource);
        $this->assertStringContainsString('maxlength="18"', $viewSource);
        $this->assertStringContainsString('inputmode="numeric"', $viewSource);
        $this->assertStringContainsString('id="cardBrandPreview"', $viewSource);
        $this->assertStringContainsString('class="card-brand-preview"', $viewSource);
        $this->assertStringContainsString('name="card_brand"', $viewSource);
        $this->assertStringContainsString('id="installmentsSelect"', $viewSource);

        $this->assertStringContainsString('function formatDocument(value)', $scriptSource);
        $this->assertStringContainsString("$('input[name=\"client[document]\"]').on('input blur', function () {", $scriptSource);
        $this->assertStringContainsString('applyDocumentMask(this);', $scriptSource);
        $this->assertStringContainsString("$('input[name=\"client[document]\"]').each(function () {", $scriptSource);
        $this->assertStringContainsString("$('input[name=\"client[document]\"]').on('input blur', function () {", $scriptSource);
        $this->assertStringContainsString("updateCardTypeIcon(identifyCardType(($('input[name=\"card[card_number]\"]').val() || '').replace(/\\s/g, '')));", $scriptSource);
        $this->assertStringContainsString("const \$preview = $('#cardBrandPreview');", $scriptSource);
        $this->assertStringContainsString('function renderInstallmentsForCardBrand(cardType)', $scriptSource);
        $this->assertStringContainsString('function syncCreditCardBrand(cardType)', $scriptSource);
        $this->assertStringContainsString("$('input[name=\"card_brand\"]').val(cardType || '');", $scriptSource);
        $this->assertStringContainsString('return `${installmentCount}x ${formatCurrencyFromCents(installmentValueCents)}`;', $scriptSource);
    }
}
