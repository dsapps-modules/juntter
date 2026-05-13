<?php

namespace Tests\Feature;

use Tests\TestCase;

class SpaCheckoutLinksPageTest extends TestCase
{
    public function test_the_checkout_links_page_uses_icon_only_action_buttons(): void
    {
        $componentSource = file_get_contents(base_path('resources/js/spa/pages/checkout/CheckoutLinksPage.jsx'));

        $this->assertIsString($componentSource);
        $this->assertStringContainsString('function toggleLinkStatus(linkId, currentStatus)', $componentSource);
        $this->assertStringContainsString('const [statusLoadingLinkId, setStatusLoadingLinkId] = useState(null);', $componentSource);
        $this->assertStringContainsString("<div style={{ alignItems: 'center', display: 'flex', gap: 8, whiteSpace: 'nowrap' }}>", $componentSource);
        $this->assertStringContainsString("aria-busy={statusLoadingLinkId === record.id ? 'true' : undefined}", $componentSource);
        $this->assertStringContainsString("aria-label={value === 'active' ? 'Desativar link' : 'Ativar link'}", $componentSource);
        $this->assertStringContainsString('onClick={() => {', $componentSource);
        $this->assertStringContainsString('toggleLinkStatus(record.id, value);', $componentSource);
        $this->assertStringContainsString('onKeyDown={(event) => {', $componentSource);
        $this->assertStringContainsString("style={{ cursor: statusLoadingLinkId === record.id ? 'wait' : 'pointer', display: 'inline-flex' }}", $componentSource);
        $this->assertStringContainsString('{value}', $componentSource);
        $this->assertStringContainsString("<span style={{ display: 'inline-flex', justifyContent: 'center', marginLeft: 20, width: 16, flexShrink: 0 }}>", $componentSource);
        $this->assertStringContainsString('statusLoadingLinkId === record.id ? <Spin size="small" /> : null', $componentSource);
        $this->assertStringContainsString('aria-label="Copiar link"', $componentSource);
        $this->assertStringContainsString('aria-label="Editar link"', $componentSource);
        $this->assertStringContainsString('aria-label="Ver vendas"', $componentSource);
        $this->assertStringContainsString('aria-label="Excluir link"', $componentSource);
        $this->assertStringNotContainsString('PauseCircleOutlined', $componentSource);
        $this->assertStringNotContainsString('PlayCircleOutlined', $componentSource);
        $this->assertStringNotContainsString("title: 'Ações'", $componentSource);
        $this->assertStringNotContainsString('title="Desativar link"', $componentSource);
        $this->assertStringNotContainsString('title="Ativar link"', $componentSource);
        $this->assertDoesNotMatchRegularExpression('/<Button[^>]*>\\s*Copiar\\s*<\\/Button>/u', $componentSource);
        $this->assertDoesNotMatchRegularExpression('/<Button[^>]*>\\s*Editar\\s*<\\/Button>/u', $componentSource);
        $this->assertDoesNotMatchRegularExpression('/<Button[^>]*>\\s*Vendas\\s*<\\/Button>/u', $componentSource);
        $this->assertDoesNotMatchRegularExpression('/<Button[^>]*>\\s*Excluir\\s*<\\/Button>/u', $componentSource);
    }

    public function test_the_checkout_link_sales_page_opens_sale_details_from_table_rows(): void
    {
        $salesPageSource = file_get_contents(base_path('resources/js/spa/pages/checkout/CheckoutLinkSalesPage.jsx'));
        $detailPageSource = file_get_contents(base_path('resources/js/spa/pages/checkout/CheckoutLinkSaleDetailPage.jsx'));

        $this->assertIsString($salesPageSource);
        $this->assertStringContainsString('onRow={(record) => ({', $salesPageSource);
        $this->assertStringContainsString('navigate(`/seller/checkout-links/${params.checkoutLinkId}/vendas/${record.id}`)', $salesPageSource);
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
