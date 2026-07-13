<?php

namespace App\Http\Requests\Api\PlatformAdministrators;

use App\Rules\ValidCatalogItem;
use App\Rules\ValidDocumentNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlatformAdministratorUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $administrator = $this->route('user');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($administrator?->id)],
            'country' => ['sometimes', 'required', 'string', 'size:2', Rule::exists('countries', 'code')->where('is_active', true)],
            'document_type_id' => ['sometimes', 'required', 'integer', new ValidCatalogItem('document_types')],
            'document_number' => ['sometimes', 'required', 'string', 'max:30', new ValidDocumentNumber],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'secondary_phone' => ['sometimes', 'nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'first_name.required' => 'Los nombres son obligatorios.',
            'first_name.string' => 'Los nombres deben ser una cadena de texto.',
            'first_name.max' => 'Los nombres no pueden tener más de 255 caracteres.',
            'last_name.string' => 'Los apellidos deben ser una cadena de texto.',
            'last_name.max' => 'Los apellidos no pueden tener más de 255 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.max' => 'El correo electrónico no puede tener más de 255 caracteres.',
            'email.unique' => 'El correo electrónico ya está registrado.',
            'country.required' => 'El país es obligatorio.',
            'country.string' => 'El país debe ser una cadena de texto.',
            'country.size' => 'El país debe tener un código de 2 caracteres.',
            'country.exists' => 'El país seleccionado no existe o está inactivo.',
            'document_type_id.required' => 'El tipo de documento es obligatorio.',
            'document_type_id.integer' => 'El tipo de documento debe ser un identificador entero.',
            'document_number.required' => 'El número de documento es obligatorio.',
            'document_number.string' => 'El número de documento debe ser una cadena de texto.',
            'document_number.max' => 'El número de documento no puede tener más de 30 caracteres.',
            'phone.string' => 'El teléfono debe ser una cadena de texto.',
            'phone.max' => 'El teléfono no puede tener más de 50 caracteres.',
            'secondary_phone.string' => 'El teléfono secundario debe ser una cadena de texto.',
            'secondary_phone.max' => 'El teléfono secundario no puede tener más de 50 caracteres.',
        ];
    }
}
