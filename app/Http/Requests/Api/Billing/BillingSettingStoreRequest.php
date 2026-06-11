<?php

namespace App\Http\Requests\Api\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BillingSettingStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'due_day' => ['required', 'integer', 'between:1,31'],
            'grace_days' => ['required', 'integer', 'min:0'],
            'late_fee_type' => ['required', Rule::in(['percentage', 'fixed'])],
            'late_fee_value' => ['required', 'numeric', 'min:0'],
            'late_fee_frequency' => ['required', Rule::in(['daily', 'monthly', 'once'])],
            'apply_late_fee_automatically' => ['nullable', 'boolean'],
            'currency' => ['nullable', 'string', 'size:3'],
            'rounding_mode' => ['nullable', 'string', 'max:50'],
        ];
    }
}
