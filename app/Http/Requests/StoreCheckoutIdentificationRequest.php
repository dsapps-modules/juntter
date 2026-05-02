<?php

namespace App\Http\Requests;

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
            'customer_document' => ['required', 'string', 'max:20'],
            'customer_document_type' => ['required', 'in:cpf,cnpj'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_birth_date' => ['nullable', 'date'],
            'customer_company_name' => ['nullable', 'string', 'max:255'],
            'customer_state_registration' => ['nullable', 'string', 'max:100'],
            'customer_is_state_registration_exempt' => ['nullable', 'boolean'],
        ];
    }
}
