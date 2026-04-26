<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\DuskTestCase;

class SpaVendedorFaturamentoTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_o_painel_de_faturamento_exibe_a_interface_atualizada(): void
    {
        $this->markTestSkipped('ChromeDriver is unavailable in this environment.');
    }
}
