<?php

namespace App\Http\Requests\Api\Administrators;

use App\Rules\ValidCatalogItem;
use App\Rules\ValidDocumentNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdministratorStoreRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'country' => ['required', 'string', 'size:2', Rule::exists('countries', 'code')->where('is_active', true)],
            'document_type_id' => ['required', 'integer', new ValidCatalogItem('document_types')],
            'document_number' => [
                'required',
                'string',
                'max:30',
                new ValidDocumentNumber,
                Rule::unique('users', 'document_number')->where(fn ($query) => $query
                    ->where('country', $this->input('country'))
                    ->where('document_type_id', $this->input('document_type_id'))),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'secondary_phone' => ['nullable', 'string', 'max:50'],
            'condominium_ids' => ['required', 'array', 'min:1'],
            'condominium_ids.*' => ['integer', 'distinct', Rule::exists('condominiums', 'id')->whereNull('deleted_at')],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required_without' => 'El nombre es obligatorio cuando no se envían los nombres.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'first_name.required_without' => 'Los nombres son obligatorios cuando no se envía el nombre completo.',
            'first_name.string' => 'Los nombres deben ser una cadena de texto.',
            'first_name.max' => 'Los nombres no pueden tener más de 255 caracteres.',
            'last_name.string' => 'Los apellidos deben ser una cadena de texto.',
            'last_name.max' => 'Los apellidos no pueden tener más de 255 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.max' => 'El correo electrónico no puede tener más de 255 caracteres.',
            'email.unique' => 'Ya existe un usuario con este correo electrónico.',
            'country.required' => 'El país es obligatorio.',
            'country.string' => 'El país debe ser una cadena de texto.',
            'country.size' => 'El país debe tener un código de 2 caracteres.',
            'country.exists' => 'El país seleccionado no existe o está inactivo.',
            'document_type_id.required' => 'El tipo de documento es obligatorio.',
            'document_type_id.integer' => 'El tipo de documento debe ser un identificador entero.',
            'document_number.required' => 'El número de documento es obligatorio.',
            'document_number.string' => 'El número de documento debe ser una cadena de texto.',
            'document_number.max' => 'El número de documento no puede tener más de 30 caracteres.',
            'document_number.unique' => 'Ya existe un usuario con este país, tipo y número de documento.',
            'phone.string' => 'El teléfono debe ser una cadena de texto.',
            'phone.max' => 'El teléfono no puede tener más de 50 caracteres.',
            'secondary_phone.string' => 'El teléfono secundario debe ser una cadena de texto.',
            'secondary_phone.max' => 'El teléfono secundario no puede tener más de 50 caracteres.',
            'condominium_ids.required' => 'Debe seleccionar al menos un condominio.',
            'condominium_ids.array' => 'Los condominios deben enviarse como una lista.',
            'condominium_ids.min' => 'Debe seleccionar al menos un condominio.',
            'condominium_ids.*.integer' => 'Cada condominio debe ser un identificador entero.',
            'condominium_ids.*.distinct' => 'No se puede seleccionar el mismo condominio más de una vez.',
            'condominium_ids.*.exists' => 'Uno de los condominios seleccionados no existe.',
        ];
    }
}
