<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BoletoRequest extends FormRequest
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
            'amount' => 'required|string',
            'expiration' => 'required|date_format:Y-m-d',
            'payment_limit_date' => 'nullable|date_format:Y-m-d|after:expiration',
            'recharge' => 'nullable|boolean',
            'client.first_name' => 'required|string|max:25',
            'client.last_name' => 'required|string|max:128',
            'client.document' => 'required|string|max:20',
            'client.email' => 'required|email',
            'client.address.street' => 'required|string|max:128',
            'client.address.number' => 'required|string|max:10',
            'client.address.complement' => 'nullable|string|max:20',
            'client.address.neighborhood' => 'required|string|max:128',
            'client.address.city' => 'required|string|max:64',
            'client.address.state' => 'required|string|size:2',
            'client.address.zip_code' => 'required|string|size:9',
            'instruction.booklet' => 'required|boolean',
            'instruction.description' => 'nullable|string|max:255',
            'instruction.late_fee.amount' => 'required|string',
            'instruction.interest.amount' => 'required|string',
            'instruction.discount.amount' => 'required|string',
            'instruction.discount.limit_date' => 'required|date_format:Y-m-d|before:expiration',
        ];
    }
}