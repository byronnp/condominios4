<?php

namespace App\Http\Requests\Api\Condominiums;

use Illuminate\Foundation\Http\FormRequest;

class CondominiumIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'page.integer' => 'La página debe ser un número entero.',
            'page.min' => 'La página debe ser al menos 1.',
            'per_page.integer' => 'La cantidad de registros por página debe ser un número entero.',
            'per_page.min' => 'La cantidad de registros por página debe ser al menos 1.',
            'per_page.max' => 'La cantidad de registros por página no puede ser mayor que 100.',
        ];
    }
}
