<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartCheckoutPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'in:pix,boleto,credit_card'],
            'installments' => ['exclude_unless:payment_method,credit_card', 'required', 'integer', 'min:1', 'max:18'],
            'card' => ['exclude_unless:payment_method,credit_card', 'required', 'array'],
            'card.holder_name' => ['exclude_unless:payment_method,credit_card', 'required', 'string', 'max:255'],
            'card.holder_document' => ['exclude_unless:payment_method,credit_card', 'required', 'string', 'max:18'],
            'card.card_number' => ['exclude_unless:payment_method,credit_card', 'required', 'string', 'min:13', 'max:19'],
            'card.expiration_month' => ['exclude_unless:payment_method,credit_card', 'required', 'integer', 'min:1', 'max:12'],
            'card.expiration_year' => ['exclude_unless:payment_method,credit_card', 'required', 'integer', 'min:'.now()->year],
            'card.security_code' => ['exclude_unless:payment_method,credit_card', 'required', 'string', 'min:3', 'max:4'],
            'card_last_four' => ['nullable', 'string', 'size:4'],
            'card_brand' => ['nullable', 'string', 'max:50'],
        ];
    }
}
