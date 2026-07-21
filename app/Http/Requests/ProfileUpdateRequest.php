<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user()->id)],
            'company_logo' => ['nullable', 'image', 'max:2048'],
            'remove_company_logo' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_logo.image' => 'O logotipo deve ser uma imagem válida.',
            'company_logo.max' => 'O logotipo deve ter no máximo 2 MB.',
            'trade_name.max' => 'O nome fantasia deve ter no máximo 255 caracteres.',
            'remove_company_logo.boolean' => 'A remoção do logotipo precisa ser verdadeira ou falsa.',
        ];
    }
}
