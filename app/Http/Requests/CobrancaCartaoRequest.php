<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CobrancaCartaoRequest extends FormRequest
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
            'payment_type' => 'required|in:CREDIT',
            'amount' => 'required|string',
            'installments' => 'required|integer|min:1|max:18',
            'interest' => 'required|in:CLIENT,ESTABLISHMENT',
            'client.first_name' => 'required|string|max:20',
            'client.last_name' => 'nullable|string|max:128',
            'client.document' => 'required|string|max:20',
            'client.phone' => 'required|string|max:18',
            'client.email' => 'required|email',
            'client.address.street' => 'required|string|max:255',
            'client.address.number' => 'string|max:10',
            'client.address.complement' => 'nullable|string|max:60',
            'client.address.neighborhood' => 'required|string|max:128',
            'client.address.city' => 'required|string|max:128',
            'client.address.state' => 'required|string|size:2',
            'client.address.zip_code' => 'required|string|size:9',
            'card.holder_name' => 'required|string|max:64',
            'card.holder_document' => 'nullable|string|max:20',
            'card.card_number' => 'required|string|min:13|max:19',
            'card.expiration_month' => 'required|integer|min:1|max:12',
            'card.expiration_year' => 'required|integer|min:2025',
            'card.security_code' => 'required|string|min:3|max:4',
        ];
    }

    public function messages(): array
    {
        return [
            'amount' => 'Informe o valor da compra',
            'installments' => 'A quantidade máxima de prestações é 18',
            'interest' => 'Informe quem paga as taxas: o cliente ou o estabelecimento',
            'client.first_name' => 'O primeiro nome do cliente deve ter, no máximo, 20 caracteres',
            'client.last_name' => 'O sobrenome do cliente deve ter, no máximo, 128 caracteres',
            'client.document' => 'O número do documento informando deve ter, no máximo, 20 caracteres',
            'client.phone' => 'O telefone pode ter até 18 caracteres',
            'client.email' => 'Informe um e-mail válido ou deixe esse campo em branco',
            'client.address.street' => 'Informe o nome da rua',
            'client.address.number' => 'A informação de número pode ter até 10 caracteres',
            'client.address.complement' => 'O complemento pode ter até 60 caracteres',
            'client.address.neighborhood' => 'O nome do bairro pode ter até 128 caracteres',
            'client.address.city' => 'O nome da cidade pode ter até 128 caracteres',
            'client.address.state' => 'Use a forma com dois caracteres para informar o estado',
            'client.address.zip_code' => 'Informe do CEP sem ponto ou hífem',
            'card.holder_name' => 'O nome no cartão pode ter até 64 caracteres',
            'card.holder_document' => 'O número do documento pode ter até 20 caracteres',
            'card.card_number' => 'O número do cartão tem, no máximo, 16 caracteres; digite apenas números',
            'card.expiration_month' => 'Mês incorreto',
            'card.expiration_year' => 'Verifique o ano de expiração do cartão',
            'card.security_code' => 'Verifique o código de segurança do cartão',
        ];
    }
}