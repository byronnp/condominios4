<?php

namespace App\Http\Requests\Api\Billing;

use Illuminate\Foundation\Http\FormRequest;

class MonthlyFeeGenerateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period_year' => ['required', 'integer', 'min:2020'],
            'period_month' => ['required', 'integer', 'between:1,12'],
        ];
    }
}
