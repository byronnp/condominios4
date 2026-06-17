<?php

namespace App\Http\Requests\Api\Permissions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('code') && $this->filled(['module', 'action'])) {
            $this->merge([
                'code' => $this->input('module').'.'.$this->input('action'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'module' => ['required', 'string', 'max:100'],
            'action' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:150', 'regex:/^[a-z0-9_]+\.[a-z0-9_]+$/', Rule::unique('permissions', 'code')],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
