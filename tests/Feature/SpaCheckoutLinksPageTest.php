<?php

namespace Tests\Feature;

use Tests\TestCase;

class SpaCheckoutLinksPageTest extends TestCase
{
    public function test_the_checkout_link_form_offers_all_light_colored_themes(): void
    {
        $componentSource = file_get_contents(base_path('resources/js/spa/pages/checkout/CheckoutLinkFormPage.jsx'));

        $this->assertIsString($componentSource);
        $this->assertStringContainsString("theme: 'essential'", $componentSource);
        $this->assertStringContainsString("value: 'horizon'", $componentSource);
        $this->assertStringContainsString("name: 'Horizonte'", $componentSource);
        $this->assertStringContainsString("value: 'iris'", $componentSource);
        $this->assertStringContainsString("name: 'Íris'", $componentSource);
        $this->assertStringContainsString("value: 'atlantic'", $componentSource);
        $this->assertStringContainsString("name: 'Atlântico'", $componentSource);
    }

    public function test_the_checkout_link_form_uses_a_square_alternative_image_preview(): void
    {
        $componentSource = file_get_contents(base_path('resources/js/spa/pages/checkout/CheckoutLinkFormPage.jsx'));

        $this->assertIsString($componentSource);
        $this->assertStringContainsString('label="Imagem alternativa do produto"', $componentSource);
        $this->assertStringContainsString('extra="Envie uma imagem quadrada de 250x250 px, preferencialmente."', $componentSource);
        $this->assertStringContainsString('className="spa-product-image-preview"', $componentSource);
        $this->assertStringContainsString('aria-label={`Imagem original de ${selectedProduct.name}`}', $componentSource);
        $this->assertStringContainsString('backgroundImage: `url(${selectedProduct.image_url})`', $componentSource);
        $this->assertStringContainsString('backgroundImage: `url(${productImagePreviewUrl})`', $componentSource);
        $this->assertStringContainsString('height: 112,', $componentSource);
        $this->assertStringContainsString('minHeight: 112,', $componentSource);
        $this->assertStringContainsString('width: 112,', $componentSource);
    }

    public function test_the_checkout_link_form_can_reuse_the_last_checkout_style(): void
    {
        $componentSource = file_get_contents(base_path('resources/js/spa/pages/checkout/CheckoutLinkFormPage.jsx'));

        $this->assertIsString($componentSource);
        $this->assertStringContainsString('Utilizar estilo do último checkout', $componentSource);
        $this->assertStringContainsString('/seller/checkout-links/ultimo-estilo', $componentSource);
        $this->assertStringContainsString('function handleUseLastCheckoutStyle(event) {', $componentSource);
        $this->assertStringContainsString('async function setProductImageFromUrl(imageUrl) {', $componentSource);
        $this->assertStringContainsString('loading={copyingLastStyle}', $componentSource);
    }

    public function test_the_checkout_link_form_limits_credit_card_installments(): void
    {
        $componentSource = file_get_contents(base_path('resources/js/spa/pages/checkout/CheckoutLinkFormPage.jsx'));

        $this->assertIsString($componentSource);
        $this->assertStringContainsString('const creditCardInstallmentOptions = Array.from({ length: 18 }, (_, index) => {', $componentSource);
        $this->assertStringContainsString("const allowCreditCard = Form.useWatch('allow_credit_card', form);", $componentSource);
        $this->assertStringContainsString('max_credit_card_installments: checkoutLink.max_credit_card_installments ?? 18,', $componentSource);
        $this->assertStringContainsString('max_credit_card_installments: 18,', $componentSource);
        $this->assertStringContainsString('label="Parcelas máximas"', $componentSource);
        $this->assertStringContainsString('name="max_credit_card_installments"', $componentSource);
        $this->assertStringContainsString('<Select options={creditCardInstallmentOptions} />', $componentSource);
        $this->assertStringContainsString('Col xs={24} md={6}>', $componentSource);
        $this->assertStringContainsString('Col xs={24} md={4}>', $componentSource);
        $this->assertStringContainsString('label="Expira em"', $componentSource);
        $this->assertStringNotContainsString('md={6}>\n                                    <Form.Item label="Expira em"', $componentSource);
    }

    public function test_the_checkout_links_page_shows_a_status_dot_inside_the_link_column(): void
    {
        $componentSource = file_get_contents(base_path('resources/js/spa/pages/checkout/CheckoutLinksPage.jsx'));

        $this->assertIsString($componentSource);
        $this->assertStringContainsString('function getCheckoutLinkThumbnailUrl(record) {', $componentSource);
        $this->assertStringContainsString('className="spa-link-product-thumb"', $componentSource);
        $this->assertStringContainsString('backgroundImage: `url(${getCheckoutLinkThumbnailUrl(record)})`', $componentSource);
        $this->assertStringContainsString('Space align="start" size={12}', $componentSource);
        $this->assertStringContainsString('function resolveAvailabilityIndicatorLabel(record) {', $componentSource);
        $this->assertStringContainsString('function resolveAvailabilityIndicatorStyle(record) {', $componentSource);
        $this->assertStringContainsString("backgroundColor: resolveAvailabilityStatus(record) === 'active' ? '#22c55e' : '#ef4444'", $componentSource);
        $this->assertStringContainsString('height: 10,', $componentSource);
        $this->assertStringContainsString('width: 10,', $componentSource);
        $this->assertStringContainsString('aria-label={resolveAvailabilityIndicatorLabel(record)}', $componentSource);
        $this->assertStringContainsString('title={resolveAvailabilityIndicatorLabel(record)}', $componentSource);
        $this->assertStringContainsString('<Typography.Text strong>{value}</Typography.Text>', $componentSource);
        $this->assertStringNotContainsString('Gerencie os links públicos que serão usados no site do vendedor.', $componentSource);
        $this->assertStringContainsString('aria-label="Copiar link"', $componentSource);
        $this->assertStringContainsString('aria-label="Editar link"', $componentSource);
        $this->assertStringContainsString('aria-label="Ver vendas"', $componentSource);
        $this->assertStringContainsString('aria-label="Excluir link"', $componentSource);
        $this->assertStringNotContainsString("title: 'Status'", $componentSource);
        $this->assertDoesNotMatchRegularExpression('/<Button[^>]*>\\s*Copiar\\s*<\\/Button>/u', $componentSource);
        $this->assertDoesNotMatchRegularExpression('/<Button[^>]*>\\s*Editar\\s*<\\/Button>/u', $componentSource);
        $this->assertDoesNotMatchRegularExpression('/<Button[^>]*>\\s*Vendas\\s*<\\/Button>/u', $componentSource);
        $this->assertDoesNotMatchRegularExpression('/<Button[^>]*>\\s*Excluir\\s*<\\/Button>/u', $componentSource);
    }

    public function test_the_checkout_link_sales_page_opens_sale_details_from_table_rows(): void
    {
        $salesPageSource = file_get_contents(base_path('resources/js/spa/pages/checkout/CheckoutLinkSalesPage.jsx'));
        $salesBladeSource = file_get_contents(base_path('resources/views/seller/checkout-links/sales.blade.php'));
        $detailPageSource = file_get_contents(base_path('resources/js/spa/pages/checkout/CheckoutLinkSaleDetailPage.jsx'));

        $this->assertIsString($salesPageSource);
        $this->assertIsString($salesBladeSource);
        $this->assertStringContainsString('onRow={(record) => ({', $salesPageSource);
        $this->assertStringContainsString('navigate(`/seller/checkout-links/${params.checkoutLinkId}/vendas/${record.id}`)', $salesPageSource);
        $this->assertStringNotContainsString('Acompanhe os pedidos gerados a partir dos links de checkout.', $salesPageSource);
        $this->assertStringContainsString("title: 'Data'", $salesPageSource);
        $this->assertStringContainsString("render: (value) => (value ? dayjs(value).format('DD/MM/YYYY') : '-')", $salesPageSource);
        $this->assertStringContainsString('<th>Data</th>', $salesBladeSource);
        $this->assertStringContainsString('created_at?->format(\'d/m/Y\') ?? \'-\'', $salesBladeSource);
        $this->assertStringContainsString("fetch('/api/spa/perfil'", $detailPageSource);
        $this->assertStringContainsString("const isAdminUser = ['admin', 'super_admin'].includes(accessLevel);", $detailPageSource);
        $this->assertStringContainsString('{isAdminUser ? (', $detailPageSource);
        $this->assertStringContainsString('Detalhes da venda', $detailPageSource);
        $this->assertStringContainsString('Dados do cliente', $detailPageSource);
        $this->assertStringContainsString('Endereço de entrega', $detailPageSource);
        $this->assertStringContainsString('Dados do pagamento', $detailPageSource);
        $this->assertStringContainsString('Sessão do checkout', $detailPageSource);
    }
}
