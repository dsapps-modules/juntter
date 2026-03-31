<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PagamentoClienteValidationTest extends TestCase
{
    public function test_zip_code_with_mask_matches_expected_length(): void
    {
        $validator = Validator::make(
            ['client' => ['address' => ['zip_code' => '12345-678']]],
            ['client.address.zip_code' => 'required|string|size:9']
        );

        $this->assertTrue($validator->passes());
    }
}
