<?php

namespace App\Http\Requests\Api\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BankStatementImportStoreRequest extends FormRequest
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
            'period_year' => ['required', 'integer', 'min:2020'],
            'period_month' => ['required', 'integer', 'between:1,12'],
            'original_file_name' => ['nullable', 'string', 'max:255'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.transaction_date' => ['required', 'date'],
            'rows.*.reference' => ['nullable', 'string', 'max:255'],
            'rows.*.voucher_number' => ['nullable', 'string', 'max:255'],
            'rows.*.description' => ['nullable', 'string'],
            'rows.*.amount' => ['required', 'numeric'],
            'rows.*.direction' => ['required', Rule::in(['credit', 'debit'])],
        ];
    }
}
