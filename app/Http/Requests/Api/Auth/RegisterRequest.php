<?php

namespace App\Http\Requests\Api\Auth;

use App\Rules\ValidCatalogItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'country' => ['required', 'string', 'size:2'],
            'document_type_id' => ['required', 'integer', new ValidCatalogItem('document_types')],
            'document_number' => [
                'required',
                'string',
                'max:30',
                Rule::unique('users', 'document_number')->where(fn ($query) => $query
                    ->where('country', $this->input('country'))
                    ->where('document_type_id', $this->input('document_type_id'))
                    ->whereNull('deleted_at')),
            ],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
