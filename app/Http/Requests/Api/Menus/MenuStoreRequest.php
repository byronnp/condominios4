<?php

namespace App\Http\Requests\Api\Menus;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:menus,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100', 'unique:menus,code'],
            'path' => [$this->input('parent_id') ? 'required' : 'nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', Rule::exists('permissions', 'id')->where('is_active', true)],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.integer' => 'El menú padre debe ser un identificador entero.',
            'parent_id.exists' => 'El menú padre seleccionado no existe.',
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'code.string' => 'El código debe ser una cadena de texto.',
            'code.max' => 'El código no puede superar los 100 caracteres.',
            'code.unique' => 'Ya existe un menú con ese código.',
            'path.required' => 'La ruta es obligatoria para los submenús.',
            'path.string' => 'La ruta debe ser una cadena de texto.',
            'path.max' => 'La ruta no puede superar los 255 caracteres.',
            'icon.string' => 'El ícono debe ser una cadena de texto.',
            'icon.max' => 'El ícono no puede superar los 100 caracteres.',
            'sort_order.integer' => 'El orden debe ser un número entero.',
            'sort_order.min' => 'El orden no puede ser negativo.',
            'permission_ids.array' => 'Los permisos deben enviarse como un arreglo.',
            'permission_ids.*.integer' => 'Cada permiso debe ser un entero válido.',
            'permission_ids.*.exists' => 'Uno de los permisos seleccionados no existe o está inactivo.',
            'is_active.boolean' => 'El estado debe ser verdadero o falso.',
        ];
    }
}
