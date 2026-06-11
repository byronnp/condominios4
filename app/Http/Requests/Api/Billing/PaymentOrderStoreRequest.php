<?php

namespace App\Http\Requests\Api\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentOrderStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $condominium = $this->route('condominium');

        return [
            'unit_id' => ['required', 'integer', Rule::exists('units', 'id')->where('condominium_id', $condominium?->id)],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expires_at' => ['nullable', 'date'],
        ];
    }
}
