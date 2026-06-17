<?php

namespace App\Http\Requests\Api\Units;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitBillingResponsibleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ];
    }
}
