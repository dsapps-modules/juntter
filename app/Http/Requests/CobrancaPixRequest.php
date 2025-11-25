<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CobrancaPixRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_type' => 'required|in:PIX',
            'amount' => 'required|string',
            'interest' => 'required|in:CLIENT,ESTABLISHMENT',
            'client.first_name' => 'nullable|string|max:20',
            'client.last_name' => 'nullable|string|max:255',
            'client.document' => 'nullable|string|max:20',
            'client.phone' => 'nullable|string|max:18',
            'client.email' => 'nullable|email',
            'info_additional' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'amount' => 'Informe o valor do pix',
            'interest' => 'Informe quem paga as taxas: o cliente ou o estabelecimento',
            'client.first_name' => 'O primeiro nome do cliente deve ter, no máximo, 20 caracteres',
            'client.last_name' => 'O sobrenome do cliente deve ter, no máximo, 128 caracteres',
            'client.document' => 'O número do documento informando deve ter, no máximo, 20 caracteres',
            'client.phone' => 'O telefone pode ter até 18 caracteres',
            'client.email' => 'Informe um e-mail válido ou deixe esse campo em branco',
            'info_additional' => 'As observações podem ter até 200 caracteres',
        ];
    }
}