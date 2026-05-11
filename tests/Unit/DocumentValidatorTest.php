<?php

namespace Tests\Unit;

use App\Helpers\DocumentValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DocumentValidatorTest extends TestCase
{
    #[DataProvider('validDocumentsProvider')]
    public function test_it_accepts_valid_documents(string $document): void
    {
        $this->assertTrue(DocumentValidator::isValidDocument($document));
    }

    #[DataProvider('invalidDocumentsProvider')]
    public function test_it_rejects_invalid_documents(string $document): void
    {
        $this->assertFalse(DocumentValidator::isValidDocument($document));
    }

    public static function validDocumentsProvider(): array
    {
        return [
            ['123.456.789-09'],
            ['12345678909'],
            ['04.252.011/0001-10'],
            ['04252011000110'],
        ];
    }

    public static function invalidDocumentsProvider(): array
    {
        return [
            ['111.111.111-11'],
            ['11111111111'],
            ['11.111.111/1111-11'],
            ['11111111111111'],
            ['123'],
        ];
    }
}
