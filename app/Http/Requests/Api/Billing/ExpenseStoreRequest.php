<?php

namespace App\Http\Requests\Api\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $condominium = $this->route('condominium');

        return [
            'expense_category_id' => ['required', 'integer', Rule::exists('expense_categories', 'id')->where('condominium_id', $condominium?->id)],
            'condominium_payment_method_id' => ['nullable', 'integer', Rule::exists('condominium_payment_methods', 'id')->where('condominium_id', $condominium?->id)],
            'supplier_name' => ['required', 'string', 'max:255'],
            'supplier_document' => ['nullable', 'string', 'max:50'],
            'description' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'paid_at' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'voucher_number' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['pending', 'paid', 'cancelled', 'rejected'])],
        ];
    }
}
