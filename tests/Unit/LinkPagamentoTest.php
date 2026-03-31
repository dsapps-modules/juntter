<?php

namespace Tests\Unit;

use App\Models\LinkPagamento;
use PHPUnit\Framework\TestCase;

class LinkPagamentoTest extends TestCase
{
    public function test_normaliza_parcelas_numericas_em_lista_progressiva(): void
    {
        $linkPagamento = new LinkPagamento;
        $linkPagamento->parcelas = 3;

        $this->assertSame([1, 2, 3], $linkPagamento->parcelas);
        $this->assertSame(3, $linkPagamento->parcelas_maximas);
        $this->assertTrue($linkPagamento->permiteParcelamento(2));
        $this->assertFalse($linkPagamento->permiteParcelamento(4));
    }

    public function test_normaliza_formato_legado_json_com_installments(): void
    {
        $linkPagamento = new LinkPagamento;
        $linkPagamento->parcelas = '{"installments":4}';

        $this->assertSame([1, 2, 3, 4], $linkPagamento->parcelas);
        $this->assertSame([1, 2, 3, 4], $linkPagamento->parcelas_permitidas);
    }

    public function test_preserva_lista_de_parcelas_ao_definir_array(): void
    {
        $linkPagamento = new LinkPagamento;
        $linkPagamento->parcelas = [1, 3, 2, 3];

        $this->assertSame([1, 2, 3], $linkPagamento->parcelas);
    }
}
