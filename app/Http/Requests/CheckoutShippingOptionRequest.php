<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutShippingOptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isVendedor();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'eta_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome do frete.',
            'price.required' => 'Informe o valor do frete.',
            'price.numeric' => 'O valor do frete deve ser numérico.',
            'eta_days.integer' => 'O prazo de entrega deve ser um número inteiro.',
            'eta_days.min' => 'O prazo de entrega não pode ser negativo.',
            'eta_days.max' => 'O prazo de entrega não pode passar de 365 dias.',
        ];
    }
}
