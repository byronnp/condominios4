<?php

namespace App\Http\Requests\Api\Operations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommonAreaReservationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $condominium = $this->route('condominium');

        return [
            'common_area_id' => ['required', 'integer', Rule::exists('common_areas', 'id')->where('condominium_id', $condominium?->id)->where('is_active', true)->where('is_reservable', true)],
            'unit_id' => ['required', 'integer', Rule::exists('units', 'id')->where('condominium_id', $condominium?->id)],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'attendees_count' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'common_area_id.required' => 'El área común es obligatoria.',
            'common_area_id.integer' => 'El área común debe ser un identificador entero.',
            'common_area_id.exists' => 'El área común no existe, está inactiva o no admite reservas.',
            'unit_id.required' => 'La unidad es obligatoria.',
            'unit_id.integer' => 'La unidad debe ser un identificador entero.',
            'unit_id.exists' => 'La unidad no pertenece al condominio indicado.',
            'starts_at.required' => 'La fecha y hora de inicio son obligatorias.',
            'starts_at.date' => 'La fecha y hora de inicio no tienen un formato válido.',
            'ends_at.required' => 'La fecha y hora de finalización son obligatorias.',
            'ends_at.date' => 'La fecha y hora de finalización no tienen un formato válido.',
            'ends_at.after' => 'La fecha y hora de finalización deben ser posteriores al inicio.',
            'attendees_count.integer' => 'La cantidad de asistentes debe ser un número entero.',
            'attendees_count.min' => 'La cantidad de asistentes debe ser al menos 1.',
            'notes.string' => 'Las observaciones deben ser una cadena de texto.',
        ];
    }
}
