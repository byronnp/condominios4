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
            'common_area_id' => ['required', 'integer', Rule::exists('common_areas', 'id')->where('condominium_id', $condominium?->id)->where('is_active', true)],
            'unit_id' => ['required', 'integer', Rule::exists('units', 'id')->where('condominium_id', $condominium?->id)],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'attendees_count' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
