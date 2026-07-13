<?php

namespace App\Http\Requests\Api\Roles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $condominium = $this->route('condominium');
        $role = $this->route('role');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('roles', 'code')
                    ->ignore($role?->id)
                    ->where('condominium_id', $condominium?->id),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'code.string' => 'El código debe ser una cadena de texto.',
            'code.max' => 'El código no puede superar los 100 caracteres.',
            'code.unique' => 'Ya existe un rol con ese código en el condominio.',
            'description.string' => 'La descripción debe ser una cadena de texto.',
            'is_active.boolean' => 'El estado debe ser verdadero o falso.',
        ];
    }
}
