<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmCheckoutAntifraudAuthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'string'],
            'status' => ['required', 'string', 'in:AUTH_FLOW_COMPLETED,AUTH_NOT_SUPPORTED,CHANGE_PAYMENT_METHOD'],
            'authentication_status' => ['required', 'string', 'in:AUTHENTICATED,NOT_AUTHENTICATED'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'O identificador da autenticação 3DS é obrigatório.',
            'status.required' => 'O status da autenticação 3DS é obrigatório.',
            'status.in' => 'O status da autenticação 3DS é inválido.',
            'authentication_status.required' => 'O resultado da autenticação 3DS é obrigatório.',
            'authentication_status.in' => 'O resultado da autenticação 3DS é inválido.',
        ];
    }
}
