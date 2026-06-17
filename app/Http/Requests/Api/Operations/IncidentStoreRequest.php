<?php

namespace App\Http\Requests\Api\Operations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IncidentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $condominium = $this->route('condominium');

        return [
            'unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')->where('condominium_id', $condominium?->id)],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'critical'])],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}
