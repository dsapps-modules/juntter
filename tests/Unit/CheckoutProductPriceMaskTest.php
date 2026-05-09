<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CheckoutProductPriceMaskTest extends TestCase
{
    public function test_currency_mask_accumulates_digits_like_a_brazilian_money_input(): void
    {
        $value = $this->formatCurrencyInput(0);
        $sequence = [$value];

        foreach (['2', '3', '4', '5', '6'] as $digit) {
            $value = $this->formatCurrencyInput($this->extractDigits($value).$digit);
            $sequence[] = $value;
        }

        $this->assertSame([
            'R$ 0,00',
            'R$ 0,02',
            'R$ 0,23',
            'R$ 2,34',
            'R$ 23,45',
            'R$ 234,56',
        ], $sequence);
    }

    private function extractDigits(string|int|float $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    private function formatCurrencyInput(string|int|float $value): string
    {
        $digits = $this->extractDigits($value);

        if ($digits === '') {
            return 'R$ 0,00';
        }

        $numericValue = ((float) $digits) / 100;
        $parts = explode('.', number_format($numericValue, 2, '.', ''));
        $formattedInteger = number_format((float) $parts[0], 0, ',', '.');

        return 'R$ '.$formattedInteger.','.$parts[1];
    }
}
