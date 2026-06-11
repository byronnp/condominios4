<?php

namespace App\Http\Requests\Api\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BankReconciliationStoreRequest extends FormRequest
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
            'bank_statement_import_id' => ['nullable', 'integer', 'exists:bank_statement_imports,id'],
            'period_year' => ['required', 'integer', 'min:2020'],
            'period_month' => ['required', 'integer', 'between:1,12'],
            'bank_statement_balance' => ['required', 'numeric'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
