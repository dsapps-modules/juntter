<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isVendedor();
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive,archived'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'unit_price' => ['nullable', 'numeric', 'min:0.01'],
            'allow_pix' => ['nullable', 'boolean'],
            'allow_boleto' => ['nullable', 'boolean'],
            'allow_credit_card' => ['nullable', 'boolean'],
            'pix_discount_type' => ['nullable', 'in:none,fixed,percentage'],
            'pix_discount_value' => ['nullable', 'numeric', 'min:0'],
            'boleto_discount_type' => ['nullable', 'in:none,fixed,percentage'],
            'boleto_discount_value' => ['nullable', 'numeric', 'min:0'],
            'free_shipping' => ['nullable', 'boolean'],
            'success_url' => ['nullable', 'url'],
            'failure_url' => ['nullable', 'url'],
            'expires_at' => ['nullable', 'date'],
            'visual_config' => ['nullable', 'array'],
        ];
    }
}
