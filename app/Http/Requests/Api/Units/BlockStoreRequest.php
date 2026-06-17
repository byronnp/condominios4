<?php

namespace App\Http\Requests\Api\Units;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BlockStoreRequest extends FormRequest
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
            'code' => ['nullable', 'string', 'max:100', Rule::unique('condominium_blocks', 'code')->where('condominium_id', $condominium?->id)],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
