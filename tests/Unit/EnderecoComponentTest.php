<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class EnderecoComponentTest extends TestCase
{
    public function test_address_fields_are_rendered_in_the_expected_order(): void
    {
        $html = Blade::render('<x-form.endereco />');

        $this->assertMatchesRegularExpression(
            '/<div class="row">.*client\[address\]\[zip_code\].*client\[address\]\[street\].*<\/div>\s*<div class="row">.*client\[address\]\[number\].*client\[address\]\[complement\].*client\[address\]\[neighborhood\].*<\/div>/s',
            $html
        );
    }
}
