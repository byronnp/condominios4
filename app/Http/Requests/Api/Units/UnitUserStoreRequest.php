<?php

namespace App\Http\Requests\Api\Units;

use App\Rules\ValidCatalogItem;
use Illuminate\Foundation\Http\FormRequest;

class UnitUserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'required_without:first_name', 'string', 'max:255'],
            'first_name' => ['nullable', 'required_without:name', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'string', 'size:2'],
            'document_type_id' => ['required', 'integer', new ValidCatalogItem('document_types')],
            'document_number' => ['required', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:50'],
            'secondary_phone' => ['nullable', 'string', 'max:50'],
            'relationship_type_id' => ['required', 'integer', new ValidCatalogItem('resident_relationship_types')],
            'started_at' => ['nullable', 'date'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'is_primary' => ['nullable', 'boolean'],
            'is_billing_responsible' => ['nullable', 'boolean'],
        ];
    }
}
