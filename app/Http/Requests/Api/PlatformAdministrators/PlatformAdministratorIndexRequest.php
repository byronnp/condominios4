<?php

namespace App\Http\Requests\Api\PlatformAdministrators;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlatformAdministratorIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'search.string' => 'La búsqueda debe ser una cadena de texto.',
            'search.max' => 'La búsqueda no puede tener más de 255 caracteres.',
            'status.in' => 'El estado debe ser active o inactive.',
            'page.integer' => 'La página debe ser un número entero.',
            'page.min' => 'La página debe ser mayor o igual a 1.',
            'per_page.integer' => 'La cantidad por página debe ser un número entero.',
            'per_page.min' => 'La cantidad por página debe ser mayor o igual a 1.',
            'per_page.max' => 'La cantidad por página no puede ser mayor a 100.',
        ];
    }
}
