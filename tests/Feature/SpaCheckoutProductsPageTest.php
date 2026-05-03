<?php

namespace Tests\Feature;

use Tests\TestCase;

class SpaCheckoutProductsPageTest extends TestCase
{
    public function test_the_checkout_products_page_uses_icon_only_action_buttons(): void
    {
        $componentSource = file_get_contents(base_path('resources/js/spa/pages/checkout/CheckoutProductsPage.jsx'));

        $this->assertIsString($componentSource);
        $this->assertStringContainsString('async function toggleProductStatus(product)', $componentSource);
        $this->assertStringContainsString('const [statusLoadingProductId, setStatusLoadingProductId] = useState(null);', $componentSource);
        $this->assertStringContainsString('statusLoadingProductId === record.id ? <Spin size="small" /> : null', $componentSource);
        $this->assertStringContainsString("aria-busy={statusLoadingProductId === record.id ? 'true' : undefined}", $componentSource);
        $this->assertStringContainsString("aria-label={value === 'active' ? 'Desativar produto' : 'Ativar produto'}", $componentSource);
        $this->assertStringContainsString('onClick={() => {', $componentSource);
        $this->assertStringContainsString('toggleProductStatus(record);', $componentSource);
        $this->assertStringContainsString('onKeyDown={(event) => {', $componentSource);
        $this->assertStringContainsString('style={{ cursor: statusLoadingProductId === record.id ? \'wait\' : \'pointer\' }}', $componentSource);
        $this->assertStringContainsString('aria-label="Editar produto"', $componentSource);
        $this->assertStringContainsString('aria-label="Excluir produto"', $componentSource);
        $this->assertStringContainsString('icon={<EditOutlined />}', $componentSource);
        $this->assertStringContainsString('icon={<DeleteOutlined />}', $componentSource);
        $this->assertStringNotContainsString("title: 'Ações'", $componentSource);
        $this->assertStringNotContainsString('title="Desativar produto"', $componentSource);
        $this->assertStringNotContainsString('title="Ativar produto"', $componentSource);
        $this->assertDoesNotMatchRegularExpression('/<Button[^>]*>\\s*Editar\\s*<\\/Button>/u', $componentSource);
        $this->assertDoesNotMatchRegularExpression('/<Button[^>]*>\\s*Excluir\\s*<\\/Button>/u', $componentSource);
    }
}
