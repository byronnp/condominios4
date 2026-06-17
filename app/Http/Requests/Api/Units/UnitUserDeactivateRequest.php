<?php

namespace App\Http\Requests\Api\Units;

use Illuminate\Foundation\Http\FormRequest;

class UnitUserDeactivateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ended_at' => ['required', 'date'],
            'disable_access' => ['nullable', 'boolean'],
        ];
    }
}
