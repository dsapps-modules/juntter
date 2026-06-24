<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmElectronicSignatureCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'verification_code' => ['required', 'string', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'verification_code.required' => 'Informe o código de verificação.',
            'verification_code.digits' => 'O código de verificação deve ter 6 dígitos.',
        ];
    }
}
