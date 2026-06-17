<?php

namespace App\Http\Requests\Api\Operations;

use App\Rules\ValidCatalogItem;
use Illuminate\Foundation\Http\FormRequest;

class VisitorStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'document_type_id' => ['nullable', 'integer', new ValidCatalogItem('document_types')],
            'document_number' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
