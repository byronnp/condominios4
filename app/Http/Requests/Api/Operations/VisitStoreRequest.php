<?php

namespace App\Http\Requests\Api\Operations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VisitStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $condominium = $this->route('condominium');

        return [
            'unit_id' => ['required', 'integer', Rule::exists('units', 'id')->where('condominium_id', $condominium?->id)],
            'visitor_id' => ['required', 'integer', Rule::exists('visitors', 'id')->where('condominium_id', $condominium?->id)->where('is_active', true)],
            'purpose' => ['nullable', 'string', 'max:255'],
            'scheduled_at' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:scheduled_at'],
            'status' => ['nullable', Rule::in(['pending', 'authorized'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
