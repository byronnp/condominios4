<?php

namespace App\Http\Requests\Api\Units;

use App\Models\Unit;
use App\Rules\ValidCatalogItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UnitUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $condominium = $this->route('condominium');
        $unit = $this->route('unit');

        return [
            'condominium_block_id' => ['sometimes', 'nullable', 'integer', Rule::exists('condominium_blocks', 'id')->where(fn ($query) => $query->where('condominium_id', $condominium?->id)->whereNull('deleted_at'))],
            'parent_unit_id' => ['sometimes', 'nullable', 'integer', Rule::exists('units', 'id')->where(fn ($query) => $query->where('condominium_id', $condominium?->id)->whereNull('deleted_at'))],
            'unit_type_id' => ['sometimes', 'required', 'integer', new ValidCatalogItem('unit_types')],
            'code' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('units', 'code')->where('condominium_id', $condominium?->id)->ignore($unit?->id)],
            'number' => ['sometimes', 'required', 'string', 'max:100'],
            'floor' => ['sometimes', 'nullable', 'string', 'max:50'],
            'area_m2' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'current_aliquot_percentage' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'aliquot_starts_on' => ['sometimes', 'nullable', 'date', 'required_with:current_aliquot_percentage'],
            'is_assignable' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $parentUnitId = $this->input('parent_unit_id');
            $unit = $this->route('unit');

            if ($parentUnitId === null || ! $unit instanceof Unit) {
                return;
            }

            if ((int) $parentUnitId === $unit->id) {
                $validator->errors()->add('parent_unit_id', 'Una unidad no puede ser su propia unidad principal.');

                return;
            }

            $parent = Unit::query()->find($parentUnitId);

            if ($parent?->parent_unit_id !== null) {
                $validator->errors()->add('parent_unit_id', 'No se permite relacionar unidades en más de dos niveles.');
            }

            if ($unit->childUnits()->exists()) {
                $validator->errors()->add('parent_unit_id', 'Una unidad que tiene unidades asociadas no puede convertirse en unidad hija.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'condominium_block_id.integer' => 'El bloque debe ser un identificador entero.',
            'condominium_block_id.exists' => 'El bloque seleccionado no pertenece al condominio.',
            'parent_unit_id.integer' => 'La unidad principal debe ser un identificador entero.',
            'parent_unit_id.exists' => 'La unidad principal seleccionada no pertenece al condominio.',
            'unit_type_id.required' => 'El tipo de unidad es obligatorio.',
            'unit_type_id.integer' => 'El tipo de unidad debe ser un identificador entero.',
            'code.required' => 'El código de la unidad es obligatorio.',
            'code.string' => 'El código de la unidad debe ser texto.',
            'code.max' => 'El código de la unidad no puede superar los 100 caracteres.',
            'code.unique' => 'Ya existe una unidad con este código en el condominio.',
            'number.required' => 'El número de la unidad es obligatorio.',
            'number.string' => 'El número de la unidad debe ser texto.',
            'number.max' => 'El número de la unidad no puede superar los 100 caracteres.',
            'floor.string' => 'El piso debe ser texto.',
            'floor.max' => 'El piso no puede superar los 50 caracteres.',
            'area_m2.numeric' => 'El área debe ser un valor numérico.',
            'area_m2.min' => 'El área no puede ser negativa.',
            'current_aliquot_percentage.numeric' => 'El porcentaje de alícuota debe ser numérico.',
            'current_aliquot_percentage.min' => 'El porcentaje de alícuota no puede ser negativo.',
            'current_aliquot_percentage.max' => 'El porcentaje de alícuota no puede ser mayor que 100.',
            'aliquot_starts_on.date' => 'La fecha de inicio de la alícuota no es válida.',
            'aliquot_starts_on.required_with' => 'La fecha de inicio es obligatoria al actualizar la alícuota.',
            'is_assignable.boolean' => 'El campo asignable debe ser verdadero o falso.',
            'is_active.boolean' => 'El estado debe ser verdadero o falso.',
        ];
    }
}
