<?php

namespace App\Http\Requests\Api\Condominiums;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CondominiumPaymentMethodStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'catalog_item_id' => ['required', 'integer', Rule::exists('catalog_items', 'id')->where('is_active', true)],
            'account_holder' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_type' => ['nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'identification' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'instructions' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
