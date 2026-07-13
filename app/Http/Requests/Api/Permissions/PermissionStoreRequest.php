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

    public function messages(): array
    {
        return [
            'module.required' => 'El módulo es obligatorio.',
            'module.string' => 'El módulo debe ser una cadena de texto.',
            'module.max' => 'El módulo no puede superar los 100 caracteres.',
            'action.required' => 'La acción es obligatoria.',
            'action.string' => 'La acción debe ser una cadena de texto.',
            'action.max' => 'La acción no puede superar los 100 caracteres.',
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'code.required' => 'El código es obligatorio.',
            'code.string' => 'El código debe ser una cadena de texto.',
            'code.max' => 'El código no puede superar los 150 caracteres.',
            'code.regex' => 'El código debe tener el formato module.action.',
            'code.unique' => 'Ya existe un permiso con ese código.',
            'description.string' => 'La descripción debe ser una cadena de texto.',
            'is_active.boolean' => 'El estado debe ser verdadero o falso.',
        ];
    }
}
