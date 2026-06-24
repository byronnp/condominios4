<?php

namespace App\Http\Requests\Api\Administrators;

use App\Rules\ValidCatalogItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdministratorUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $administrator = $this->route('administrator');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($administrator?->id)],
            'country' => ['sometimes', 'required', 'string', 'size:2', Rule::exists('countries', 'code')->where('is_active', true)],
            'document_type_id' => ['sometimes', 'required', 'integer', new ValidCatalogItem('document_types')],
            'document_number' => ['sometimes', 'required', 'string', 'max:30'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'secondary_phone' => ['sometimes', 'nullable', 'string', 'max:50'],
        ];
    }
}
