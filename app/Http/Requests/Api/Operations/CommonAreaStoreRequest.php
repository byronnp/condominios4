<?php

namespace App\Http\Requests\Api\Operations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommonAreaStoreRequest extends FormRequest
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
            'code' => ['nullable', 'string', 'max:100', Rule::unique('common_areas', 'code')->where('condominium_id', $condominium?->id)],
            'description' => ['nullable', 'string'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'reservation_fee' => ['nullable', 'numeric', 'min:0'],
            'is_reservable' => ['nullable', 'boolean'],
            'requires_approval' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del área común es obligatorio.',
            'name.string' => 'El nombre del área común debe ser una cadena de texto.',
            'name.max' => 'El nombre del área común no puede tener más de 255 caracteres.',
            'code.string' => 'El código debe ser una cadena de texto.',
            'code.max' => 'El código no puede tener más de 100 caracteres.',
            'code.unique' => 'Ya existe un área común con este código en el condominio.',
            'description.string' => 'La descripción debe ser una cadena de texto.',
            'capacity.integer' => 'La capacidad debe ser un número entero.',
            'capacity.min' => 'La capacidad debe ser al menos 1.',
            'reservation_fee.numeric' => 'La tarifa de reserva debe ser un número.',
            'reservation_fee.min' => 'La tarifa de reserva no puede ser negativa.',
            'is_reservable.boolean' => 'El indicador de reservable debe ser verdadero o falso.',
            'requires_approval.boolean' => 'El indicador de aprobación debe ser verdadero o falso.',
            'is_active.boolean' => 'El estado del área común debe ser verdadero o falso.',
        ];
    }
}
