<?php

namespace App\Http\Requests\Api\Roles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleSyncPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permission_ids' => ['required', 'array'],
            'permission_ids.*' => ['integer', Rule::exists('permissions', 'id')->where('is_active', true)],
        ];
    }

    public function messages(): array
    {
        return [
            'permission_ids.required' => 'Debes enviar al menos un permiso.',
            'permission_ids.array' => 'Los permisos deben enviarse como un arreglo.',
            'permission_ids.*.integer' => 'Cada permiso debe ser un entero válido.',
            'permission_ids.*.exists' => 'Uno de los permisos seleccionados no existe o está inactivo.',
        ];
    }
}
