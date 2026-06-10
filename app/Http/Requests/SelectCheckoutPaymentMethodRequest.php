<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SelectCheckoutPaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'in:pix,boleto,credit_card'],
        ];
    }
}
