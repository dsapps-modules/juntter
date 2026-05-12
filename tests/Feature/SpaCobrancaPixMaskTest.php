<?php

namespace Tests\Feature;

use Tests\TestCase;

class SpaCobrancaPixMaskTest extends TestCase
{
    public function test_cobranca_pix_page_uses_the_shared_document_mask_in_both_document_fields(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/cobranca/CobrancaPixPage.jsx'));

        $this->assertStringContainsString("interest: 'CLIENT'", $pageSource);
        $this->assertSame(2, substr_count($pageSource, 'normalize={formatDocument}'));
        $this->assertSame(2, substr_count($pageSource, 'maxLength={18}'));
    }

    public function test_cobranca_cartao_credito_page_uses_the_shared_document_mask_in_all_document_fields(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/cobranca/CobrancaCartaoCreditoPage.jsx'));

        $this->assertStringContainsString("import { formatDocument, isValidDocument } from '../../documentValidation';", $pageSource);
        $this->assertSame(3, substr_count($pageSource, 'normalize={formatDocument}'));
        $this->assertSame(3, substr_count($pageSource, 'maxLength={18}'));
    }

    public function test_cobranca_cartao_credito_page_uses_phone_mask_in_both_phone_fields(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/cobranca/CobrancaCartaoCreditoPage.jsx'));

        $this->assertStringContainsString('function formatPhone(value)', $pageSource);
        $this->assertStringContainsString('slice(0, 11)', $pageSource);
        $this->assertSame(2, substr_count($pageSource, 'normalize={formatPhone}'));
        $this->assertSame(2, substr_count($pageSource, 'maxLength={15}'));
    }

    public function test_cobranca_pix_page_uses_phone_mask_in_both_phone_fields(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/cobranca/CobrancaPixPage.jsx'));

        $this->assertStringContainsString('function formatPhone(value)', $pageSource);
        $this->assertStringContainsString('slice(0, 11)', $pageSource);
        $this->assertSame(2, substr_count($pageSource, 'normalize={formatPhone}'));
        $this->assertSame(4, substr_count($pageSource, 'inputMode="numeric"'));
        $this->assertSame(2, substr_count($pageSource, 'maxLength={15}'));
    }

    public function test_document_validation_helper_switches_to_cnpj_when_the_document_has_more_than_eleven_digits(): void
    {
        $helperSource = file_get_contents(base_path('resources/js/spa/documentValidation.js'));

        $this->assertStringContainsString('export function getDocumentType(value)', $helperSource);
        $this->assertStringContainsString('digits.length > 11', $helperSource);
        $this->assertStringContainsString("return 'cnpj';", $helperSource);
        $this->assertStringContainsString('export function formatDocument(value)', $helperSource);
    }
}
