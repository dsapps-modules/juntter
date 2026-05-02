<?php

namespace Tests\Feature;

use Tests\TestCase;

class CheckoutPageTest extends TestCase
{
    public function test_the_checkout_page_uses_the_updated_acelerar_rates(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('id="acelerar"', false);

        foreach ([
            'data-rate="6.53"',
            'data-rate="8.35"',
            'data-rate="10.15"',
            'data-rate="11.87"',
            'data-rate="13.01"',
            'data-rate="14.11"',
            'data-rate="15.21"',
            'data-rate="16.28"',
            'data-rate="17.32"',
            'data-rate="18.36"',
            'data-rate="19.37"',
            'data-rate="20.58"',
            'data-rate="21.79"',
            'data-rate="23.00"',
            'data-rate="24.21"',
            'data-rate="25.42"',
            'data-rate="26.63"',
        ] as $rate) {
            $response->assertSee($rate, false);
        }

        $response->assertSee('13,01%', false);
        $response->assertSee('id="turbo"', false);

        foreach ([
            'data-rate="5.63"',
            'data-rate="7.20"',
            'data-rate="8.75"',
            'data-rate="10.23"',
            'data-rate="11.21"',
            'data-rate="12.17"',
            'data-rate="13.11"',
            'data-rate="14.03"',
            'data-rate="14.93"',
            'data-rate="15.83"',
            'data-rate="16.70"',
            'data-rate="17.71"',
            'data-rate="18.73"',
            'data-rate="19.74"',
            'data-rate="20.76"',
            'data-rate="21.77"',
            'data-rate="22.79"',
        ] as $rate) {
            $response->assertSee($rate, false);
        }

        $response->assertSee('11,21%', false);
        $response->assertSee('id="economico"', false);

        foreach ([
            'data-rate="3.75"',
            'data-rate="3.75"',
            'data-rate="3.75"',
            'data-rate="3.75"',
            'data-rate="3.75"',
            'data-rate="3.75"',
            'data-rate="3.75"',
            'data-rate="3.75"',
            'data-rate="3.75"',
            'data-rate="3.75"',
            'data-rate="3.75"',
            'data-rate="5.98"',
            'data-rate="5.98"',
            'data-rate="5.98"',
            'data-rate="5.98"',
            'data-rate="5.98"',
            'data-rate="5.98"',
        ] as $rate) {
            $response->assertSee($rate, false);
        }

        $response->assertSee('3,75%', false);
    }
}
