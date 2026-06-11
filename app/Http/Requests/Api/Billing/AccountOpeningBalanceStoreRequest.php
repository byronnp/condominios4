<?php

namespace App\Http\Requests\Api\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountOpeningBalanceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $condominium = $this->route('condominium');

        return [
            'condominium_payment_method_id' => ['required', 'integer', Rule::exists('condominium_payment_methods', 'id')->where('condominium_id', $condominium?->id)],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'opened_on' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
