<?php

namespace App\Http\Requests\Api\Condominiums;

use Illuminate\Foundation\Http\FormRequest;

class CondominiumOptionIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'search.string' => 'El criterio de búsqueda debe ser una cadena de texto.',
            'search.max' => 'El criterio de búsqueda no puede tener más de 255 caracteres.',
        ];
    }
}
