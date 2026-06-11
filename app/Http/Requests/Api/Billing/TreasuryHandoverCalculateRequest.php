<?php

namespace App\Http\Requests\Api\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TreasuryHandoverCalculateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $condominium = $this->route('condominium');

        return [
            'period_starts_on' => ['required', 'date'],
            'period_ends_on' => ['required', 'date', 'after_or_equal:period_starts_on'],
            'condominium_payment_method_id' => ['required', 'integer', Rule::exists('condominium_payment_methods', 'id')->where('condominium_id', $condominium?->id)],
        ];
    }
}
