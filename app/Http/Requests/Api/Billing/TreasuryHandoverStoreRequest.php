<?php

namespace App\Http\Requests\Api\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TreasuryHandoverStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $condominium = $this->route('condominium');

        return [
            'type' => ['required', Rule::in(['reception', 'handover'])],
            'period_starts_on' => ['required', 'date'],
            'period_ends_on' => ['nullable', 'date', 'after_or_equal:period_starts_on'],
            'condominium_payment_method_id' => ['required', 'integer', Rule::exists('condominium_payment_methods', 'id')->where('condominium_id', $condominium?->id)],
            'delivered_by_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'received_by_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'bank_balance' => ['required', 'numeric', 'min:0'],
            'cash_balance' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
