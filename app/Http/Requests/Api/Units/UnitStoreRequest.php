<?php

namespace App\Http\Requests\Api\Units;

use App\Rules\ValidCatalogItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $condominium = $this->route('condominium');

        return [
            'condominium_block_id' => ['nullable', 'integer', Rule::exists('condominium_blocks', 'id')->where('condominium_id', $condominium?->id)],
            'parent_unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')->where('condominium_id', $condominium?->id)],
            'unit_type_id' => ['required', 'integer', new ValidCatalogItem('unit_types')],
            'code' => ['required', 'string', 'max:100', Rule::unique('units', 'code')->where('condominium_id', $condominium?->id)],
            'number' => ['required', 'string', 'max:100'],
            'floor' => ['nullable', 'string', 'max:50'],
            'area_m2' => ['nullable', 'numeric', 'min:0'],
            'is_assignable' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
