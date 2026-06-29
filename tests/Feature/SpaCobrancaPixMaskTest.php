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

    public function test_cobranca_cartao_credito_page_looks_up_address_from_zipcode(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/cobranca/CobrancaCartaoCreditoPage.jsx'));

        $this->assertStringContainsString('function formatZipcode(value)', $pageSource);
        $this->assertStringContainsString('normalize={formatZipcode}', $pageSource);
        $this->assertStringContainsString('maxLength={9}', $pageSource);
        $this->assertStringContainsString('async function lookupAddressByZipcode(zipcode)', $pageSource);
        $this->assertStringContainsString('https://viacep.com.br/ws/${normalizeDigits(zipcode)}/json/', $pageSource);
        $this->assertStringContainsString('async function handleZipcodeBlur()', $pageSource);
        $this->assertStringContainsString('const stateValue = resolveStateValue(address);', $pageSource);
        $this->assertStringContainsString('form.setFieldValue([\'client\', \'address\', \'state\'], stateValue);', $pageSource);
        $this->assertStringContainsString('street: address.logradouro || \'\'', $pageSource);
        $this->assertStringContainsString('neighborhood: address.bairro || \'\'', $pageSource);
        $this->assertStringContainsString('city: address.localidade || \'\'', $pageSource);
        $this->assertStringContainsString('onBlur={handleZipcodeBlur}', $pageSource);
    }

    public function test_cobranca_boleto_page_looks_up_address_from_zipcode(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/cobranca/CobrancaBoletoPage.jsx'));

        $this->assertStringContainsString('function formatZipcode(value)', $pageSource);
        $this->assertStringContainsString('normalize={formatZipcode}', $pageSource);
        $this->assertStringContainsString('maxLength={9}', $pageSource);
        $this->assertStringContainsString('async function lookupAddressByZipcode(zipcode)', $pageSource);
        $this->assertStringContainsString('https://viacep.com.br/ws/${normalizeDigits(zipcode)}/json/', $pageSource);
        $this->assertStringContainsString('async function handleZipcodeBlur()', $pageSource);
        $this->assertStringContainsString('const stateValue = resolveStateValue(address);', $pageSource);
        $this->assertStringContainsString('form.setFieldValue([\'client\', \'address\', \'state\'], stateValue);', $pageSource);
        $this->assertStringContainsString('street: address.logradouro || \'\'', $pageSource);
        $this->assertStringContainsString('neighborhood: address.bairro || \'\'', $pageSource);
        $this->assertStringContainsString('city: address.localidade || \'\'', $pageSource);
        $this->assertStringContainsString('onBlur={handleZipcodeBlur}', $pageSource);
    }

    public function test_cobranca_boleto_page_syncs_payment_dates_from_expiration_like_the_legacy_form(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/cobranca/CobrancaBoletoPage.jsx'));

        $this->assertStringContainsString("import { formatDocument, isValidCnpj, isValidDocument } from '../../documentValidation';", $pageSource);
        $this->assertStringContainsString('label="Vencimento"', $pageSource);
        $this->assertStringContainsString('expiration: dayjs().add(1, \'day\')', $pageSource);
        $this->assertStringContainsString("payment_limit_date: dayjs().add(2, 'day')", $pageSource);
        $this->assertStringContainsString('limit_date: dayjs(),', $pageSource);
        $this->assertStringContainsString('function syncBoletoDates(form, expiration)', $pageSource);
        $this->assertStringContainsString("form.setFieldValue('payment_limit_date', expirationDate.add(1, 'day'));", $pageSource);
        $this->assertStringContainsString("form.setFieldValue(['instruction', 'discount', 'limit_date'], expirationDate.subtract(1, 'day'));", $pageSource);
        $this->assertStringContainsString('syncBoletoDates(form, form.getFieldValue(\'expiration\'));', $pageSource);
        $this->assertStringContainsString('normalize={formatDocument}', $pageSource);
        $this->assertStringContainsString('maxLength={18}', $pageSource);
        $this->assertStringContainsString('label="Telefone"', $pageSource);
        $this->assertStringContainsString('normalize={formatPhone}', $pageSource);
        $this->assertStringContainsString('maxLength={15}', $pageSource);
        $this->assertTrue(strpos($pageSource, 'label="CPF/CNPJ"') < strpos($pageSource, 'label="Telefone"'));
        $this->assertTrue(strpos($pageSource, 'label="Telefone"') < strpos($pageSource, 'label="Nome"'));
        $this->assertStringContainsString('onBlur={handleDocumentBlur}', $pageSource);
        $this->assertStringContainsString('fetch(`/checkout/cnpj/${digits}`', $pageSource);
        $this->assertStringContainsString('company_name', $pageSource);
        $this->assertStringContainsString('trade_name', $pageSource);
        $this->assertStringContainsString('company.address ?? {}', $pageSource);
        $this->assertStringContainsString('address: {', $pageSource);
        $this->assertStringContainsString('zip_code: companyAddress.zip_code ? formatZipcode(companyAddress.zip_code)', $pageSource);
        $this->assertStringContainsString('<Col xs={24}>', $pageSource);
        $this->assertStringContainsString('slice(0, 3)', $pageSource);
        $this->assertStringContainsString('onClick={() => openBoletoDetails(item)}', $pageSource);
        $this->assertStringNotContainsString('Abrir', $pageSource);
        $this->assertStringContainsString("title: 'Cliente'", $pageSource);
        $this->assertStringContainsString("dataIndex: 'title'", $pageSource);
        $this->assertStringContainsString('render: (value) => <Typography.Text strong className="spa-pix-row-title">{value}</Typography.Text>', $pageSource);
        $this->assertStringNotContainsString("title: 'ID'", $pageSource);
        $this->assertStringNotContainsString('record.raw_status ? ` • ${record.raw_status}`', $pageSource);
        $this->assertTrue(strpos($pageSource, 'label="CEP"') < strpos($pageSource, 'label="Rua"'));
        $this->assertTrue(strpos($pageSource, 'label="Estado"') < strpos($pageSource, 'label="Rua"'));
        $this->assertTrue(strpos($pageSource, 'label="Complemento"') < strpos($pageSource, 'label="Bairro"'));
        $this->assertTrue(strpos($pageSource, 'label="Bairro"') < strpos($pageSource, 'label="Cidade"'));
        $this->assertStringNotContainsString('setBoletoResult(', $pageSource);
        $this->assertStringNotContainsString('Atualizar painel', $pageSource);
        $this->assertStringContainsString('const [cancelingBoletoCode, setCancelingBoletoCode] = useState(null);', $pageSource);
        $this->assertStringContainsString('loading={cancelingBoletoCode === record.code}', $pageSource);
        $this->assertSame(4, substr_count($pageSource, 'scrollToTop();'));
        $this->assertStringContainsString('onChange={handleExpirationChange}', $pageSource);
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
