<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StorePixPayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $amount = $this->normalizeAmountToCents($value);

                    if ($amount === null) {
                        $fail('Informe um valor válido.');

                        return;
                    }

                    if ($amount < 1) {
                        $fail('O valor deve ser de pelo menos R$ 0,01.');
                    }
                },
            ],
            'pix_key_type' => ['required', 'in:PHONE,CPF,EMAIL,CNPJ,RANDOM'],
            'pix_key' => [
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! filled($value)) {
                        $fail('Informe a chave PIX.');
                    }
                },
            ],
            'electronic_signature' => ['required', 'string', 'min:4', 'max:255'],
            'description' => ['nullable', 'string', 'max:140'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Informe o valor do envio.',
            'pix_key_type.required' => 'Informe o tipo da chave PIX.',
            'pix_key_type.in' => 'O tipo da chave PIX informado é inválido.',
            'pix_key.max' => 'A chave PIX pode ter até 255 caracteres.',
            'electronic_signature.required' => 'Informe a assinatura eletrônica.',
            'electronic_signature.min' => 'A assinatura eletrônica deve ter ao menos 4 caracteres.',
            'electronic_signature.max' => 'A assinatura eletrônica pode ter até 255 caracteres.',
            'description.max' => 'A descrição pode ter até 140 caracteres.',
        ];
    }

    private function normalizeAmountToCents(mixed $value): ?int
    {
        if (! is_scalar($value)) {
            return null;
        }

        $amount = preg_replace('/[R$\s]/', '', (string) $value);

        if (! is_string($amount) || trim($amount) === '') {
            return null;
        }

        if (str_contains($amount, ',')) {
            $amount = str_replace('.', '', $amount);
            $amount = str_replace(',', '.', $amount);
        }

        if (! is_numeric($amount)) {
            return null;
        }

        return (int) round(((float) $amount) * 100);
    }
}
