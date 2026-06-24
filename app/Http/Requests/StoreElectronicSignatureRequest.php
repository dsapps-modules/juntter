<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreElectronicSignatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'electronic_signature' => ['required', 'string', 'min:4', 'max:255'],
            'electronic_signature_confirmation' => ['required', 'string', 'min:4', 'max:255', 'same:electronic_signature'],
        ];
    }

    public function messages(): array
    {
        return [
            'electronic_signature.required' => 'Informe a nova assinatura eletrônica.',
            'electronic_signature.min' => 'A nova assinatura eletrônica deve ter ao menos 4 caracteres.',
            'electronic_signature.max' => 'A nova assinatura eletrônica pode ter até 255 caracteres.',
            'electronic_signature_confirmation.required' => 'Confirme a nova assinatura eletrônica.',
            'electronic_signature_confirmation.same' => 'A confirmação da assinatura eletrônica não confere.',
        ];
    }
}
