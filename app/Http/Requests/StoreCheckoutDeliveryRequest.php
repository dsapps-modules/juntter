<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delivery_zipcode' => ['required', 'string', 'max:10'],
            'delivery_street' => ['required', 'string', 'max:255'],
            'delivery_number' => ['required', 'string', 'max:20'],
            'delivery_complement' => ['nullable', 'string', 'max:255'],
            'delivery_neighborhood' => ['required', 'string', 'max:255'],
            'delivery_city' => ['required', 'string', 'max:255'],
            'delivery_state' => ['required', 'string', 'size:2'],
            'delivery_recipient_name' => ['nullable', 'string', 'max:255'],
            'shipping_option_id' => ['nullable', 'integer'],
        ];
    }
}
