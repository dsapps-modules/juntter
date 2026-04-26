<?php

namespace Tests\Feature;

use Tests\TestCase;

class VendedoresFaturamentoPageTest extends TestCase
{
    public function test_o_componente_de_faturamento_reflete_o_layout_atualizado(): void
    {
        $componentSource = file_get_contents(base_path('resources/js/spa/pages/VendedoresFaturamentoPage.jsx'));

        $this->assertIsString($componentSource);
        $this->assertStringNotContainsString('Faturamento por loja', $componentSource);
        $this->assertStringNotContainsString('Estabelecimento ID', $componentSource);
        $this->assertStringNotContainsString('Quick View:', $componentSource);
        $this->assertSame(3, substr_count($componentSource, 'size="large"'));
    }
}
