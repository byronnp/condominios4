<?php

namespace App\Http\Requests\Api\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExtraordinaryFeeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $condominium = $this->route('condominium');

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'apply_to' => ['required', Rule::in(['all_units', 'selected_units'])],
            'unit_ids' => ['nullable', 'array'],
            'unit_ids.*' => ['integer', Rule::exists('units', 'id')->where('condominium_id', $condominium?->id)],
        ];
    }
}
