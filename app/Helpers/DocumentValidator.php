<?php

namespace App\Helpers;

class DocumentValidator
{
    public static function isValidDocument(string $document): bool
    {
        $digits = self::normalizeDigits($document);

        return match (strlen($digits)) {
            11 => self::isValidCpf($digits),
            14 => self::isValidCnpj($digits),
            default => false,
        };
    }

    public static function isValidCpf(string $digits): bool
    {
        if (strlen($digits) !== 11 || preg_match('/^(\d)\1{10}$/', $digits) === 1) {
            return false;
        }

        for ($factor = 10; $factor <= 11; $factor++) {
            $sum = 0;

            for ($index = 0; $index < $factor - 1; $index++) {
                $sum += (int) $digits[$index] * ($factor - $index);
            }

            $digit = ((($sum * 10) % 11) % 10);

            if ((int) $digits[$factor - 1] !== $digit) {
                return false;
            }
        }

        return true;
    }

    public static function isValidCnpj(string $digits): bool
    {
        if (strlen($digits) !== 14 || preg_match('/^(\d)\1{13}$/', $digits) === 1) {
            return false;
        }

        $weights = [
            [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
            [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
        ];

        foreach ($weights as $index => $weightSet) {
            $sum = 0;

            foreach ($weightSet as $position => $weight) {
                $sum += (int) $digits[$position] * $weight;
            }

            $remainder = $sum % 11;
            $digit = $remainder < 2 ? 0 : 11 - $remainder;

            if ((int) $digits[12 + $index] !== $digit) {
                return false;
            }
        }

        return true;
    }

    public static function normalizeDigits(string $document): string
    {
        return preg_replace('/\D+/', '', $document) ?? '';
    }
}
