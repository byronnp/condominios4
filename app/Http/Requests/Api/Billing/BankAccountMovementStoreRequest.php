<?php

namespace App\Http\Requests\Api\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BankAccountMovementStoreRequest extends FormRequest
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
            'type' => ['required', 'string', 'max:100'],
            'direction' => ['required', Rule::in(['credit', 'debit'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'movement_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'voucher_number' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ];
    }
}
