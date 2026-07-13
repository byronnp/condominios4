<?php

namespace App\Http\Requests\Api\Roles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleStoreRequest extends FormRequest
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
            'code' => ['nullable', 'string', 'max:100', Rule::unique('roles', 'code')->where('condominium_id', $condominium?->id)],
            'description' => ['nullable', 'string'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', Rule::exists('permissions', 'id')->where('is_active', true)],
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
            'permission_ids.array' => 'Los permisos deben enviarse como un arreglo.',
            'permission_ids.*.integer' => 'Cada permiso debe ser un entero válido.',
            'permission_ids.*.exists' => 'Uno de los permisos seleccionados no existe o está inactivo.',
            'is_active.boolean' => 'El estado debe ser verdadero o falso.',
        ];
    }
}
