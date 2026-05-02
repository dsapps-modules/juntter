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
            'installments' => ['nullable', 'integer', 'min:1', 'max:18'],
            'card_last_four' => ['nullable', 'string', 'size:4'],
            'card_brand' => ['nullable', 'string', 'max:50'],
        ];
    }
}
