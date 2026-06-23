<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isVendedor();
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('visual_config')) {
            return;
        }

        $visualConfig = $this->input('visual_config');

        if (! is_string($visualConfig)) {
            return;
        }

        $decodedVisualConfig = json_decode($visualConfig, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decodedVisualConfig)) {
            return;
        }

        $this->merge([
            'visual_config' => $decodedVisualConfig,
        ]);
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
            'request_address' => ['nullable', 'boolean'],
            'pix_discount_type' => ['nullable', 'in:none,fixed,percentage'],
            'pix_discount_value' => ['nullable', 'numeric', 'min:0'],
            'boleto_discount_type' => ['nullable', 'in:none,fixed,percentage'],
            'boleto_discount_value' => ['nullable', 'numeric', 'min:0'],
            'free_shipping' => ['nullable', 'boolean'],
            'success_url' => ['nullable', 'url'],
            'failure_url' => ['nullable', 'url'],
            'expires_at' => ['nullable', 'date'],
            'product_image' => ['nullable', 'image', 'max:5120'],
            'visual_config' => ['nullable', 'array'],
            'visual_config.store_name' => ['nullable', 'string', 'max:255'],
            'visual_config.primary_color' => ['nullable', 'string', 'max:32'],
            'visual_config.navbar_background_color' => ['nullable', 'string', 'max:32'],
            'visual_config.offer_message' => ['nullable', 'string', 'max:500'],
            'visual_config.footer_text' => ['nullable', 'string', 'max:500'],
        ];
    }
}
