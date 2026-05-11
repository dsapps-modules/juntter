<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutIdentificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_document' => [
                'required',
                'string',
                'max:20',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $documentType = $this->input('customer_document_type');

                    if ($documentType === 'cpf' && ! $this->isValidCpf((string) $value)) {
                        $fail('O CPF informado é inválido.');

                        return;
                    }

                    if ($documentType === 'cnpj' && ! $this->isValidCnpj((string) $value)) {
                        $fail('O CNPJ informado é inválido.');
                    }
                },
            ],
            'customer_document_type' => ['required', 'in:cpf,cnpj'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_birth_date' => ['nullable', 'date'],
            'customer_company_name' => ['nullable', 'string', 'max:255'],
            'customer_state_registration' => ['nullable', 'string', 'max:100'],
            'customer_is_state_registration_exempt' => ['nullable', 'boolean'],
        ];
    }

    protected function isValidCpf(string $cpf): bool
    {
        $cpf = preg_replace('/\D+/', '', $cpf) ?? '';

        if (strlen($cpf) !== 11) {
            return false;
        }

        if (preg_match('/^(\d)\1{10}$/', $cpf) === 1) {
            return false;
        }

        for ($digitPosition = 9; $digitPosition < 11; $digitPosition++) {
            $sum = 0;

            for ($index = 0; $index < $digitPosition; $index++) {
                $sum += (int) $cpf[$index] * (($digitPosition + 1) - $index);
            }

            $calculatedDigit = ((10 * $sum) % 11) % 10;

            if ((int) $cpf[$digitPosition] !== $calculatedDigit) {
                return false;
            }
        }

        return true;
    }

    protected function isValidCnpj(string $cnpj): bool
    {
        $cnpj = preg_replace('/\D+/', '', $cnpj) ?? '';

        if (strlen($cnpj) !== 14) {
            return false;
        }

        if (preg_match('/^(\d)\1{13}$/', $cnpj) === 1) {
            return false;
        }

        $digits = array_map('intval', str_split($cnpj));
        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $sum = 0;
        for ($index = 0; $index < 12; $index++) {
            $sum += $digits[$index] * $weights1[$index];
        }

        $remainder = $sum % 11;
        $firstDigit = $remainder < 2 ? 0 : 11 - $remainder;

        if ($digits[12] !== $firstDigit) {
            return false;
        }

        $sum = 0;
        for ($index = 0; $index < 13; $index++) {
            $sum += $digits[$index] * $weights2[$index];
        }

        $remainder = $sum % 11;
        $secondDigit = $remainder < 2 ? 0 : 11 - $remainder;

        return $digits[13] === $secondDigit;
    }
}
