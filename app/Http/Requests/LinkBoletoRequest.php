<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LinkBoletoRequest extends FormRequest
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
            'descricao' => 'nullable|string|max:1000',
            'valor' => 'required|string|min:1',
            'data_vencimento' => 'required|date|after:today',
            'data_limite_pagamento' => 'nullable|date|after:data_vencimento',
            'juros' => 'required|in:CLIENT,ESTABLISHMENT',
            'data_expiracao' => 'nullable|date|after:today',
            'dados_cliente_preenchidos' => 'required|array',
            'dados_cliente_preenchidos.nome' => 'required|string|max:255',
            'dados_cliente_preenchidos.sobrenome' => 'required|string|max:255',
            'dados_cliente_preenchidos.email' => 'required|email|max:255',
            'dados_cliente_preenchidos.telefone' => 'required|string|max:20',
            'dados_cliente_preenchidos.documento' => 'required|string|max:20',
            'dados_cliente_preenchidos.endereco' => 'required|array',
            'dados_cliente_preenchidos.endereco.rua' => 'required|string|max:255',
            'dados_cliente_preenchidos.endereco.numero' => 'required|string|max:20',
            'dados_cliente_preenchidos.endereco.bairro' => 'required|string|max:255',
            'dados_cliente_preenchidos.endereco.cidade' => 'required|string|max:255',
            'dados_cliente_preenchidos.endereco.estado' => 'required|string|max:2',
            'dados_cliente_preenchidos.endereco.cep' => 'required|string|max:10',
            'dados_cliente_preenchidos.endereco.complemento' => 'nullable|string|max:255',
            // Instruções do boleto
            'instrucoes_boleto' => 'required|array',
            'instrucoes_boleto.description' => 'nullable|string|max:500',
            'instrucoes_boleto.late_fee' => 'required|array',
            'instrucoes_boleto.late_fee.amount' => 'required|string',
            'instrucoes_boleto.interest' => 'required|array',
            'instrucoes_boleto.interest.amount' => 'required|string',
            'instrucoes_boleto.discount' => 'required|array',
            'instrucoes_boleto.discount.amount' => 'required|string',
            'instrucoes_boleto.discount.limit_date' => 'required|date|before:data_vencimento',
        ];
    }
}