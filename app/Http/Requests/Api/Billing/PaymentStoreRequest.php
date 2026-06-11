<?php

namespace App\Http\Requests\Api\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentStoreRequest extends FormRequest
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
            'condominium_payment_method_id' => ['nullable', 'integer', Rule::exists('condominium_payment_methods', 'id')->where('condominium_id', $condominium?->id)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'voucher_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'monthly_fee_ids' => ['nullable', 'array'],
            'monthly_fee_ids.*' => ['integer', 'exists:monthly_fees,id'],
        ];
    }
}
