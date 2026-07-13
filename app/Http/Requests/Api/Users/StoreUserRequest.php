<?php

namespace App\Http\Requests\Api\Users;

use App\Models\Role;
use App\Rules\ValidCatalogItem;
use App\Rules\ValidDocumentNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('role_id')) {
            $this->merge([
                'assignments' => [
                    ['role_id' => $this->input('role_id')],
                ],
            ]);
        }
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
            'document_number' => ['required', 'string', 'max:30', new ValidDocumentNumber, Rule::unique('users', 'document_number')->where(fn ($query) => $query->where('country', $this->input('country'))->where('document_type_id', $this->input('document_type_id')))],
            'phone' => ['nullable', 'string', 'max:50'],
            'secondary_phone' => ['nullable', 'string', 'max:50'],
            'is_access_enabled' => ['sometimes', 'boolean'],
            'role_id' => ['sometimes', 'integer'],
            'assignments' => ['required_without:role_id', 'array', 'min:1'],
            'assignments.*.role_id' => ['required', 'integer', 'distinct'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $condominium = $this->route('condominium');

            foreach ($this->input('assignments', []) as $index => $assignment) {
                $role = Role::query()->whereKey($assignment['role_id'] ?? null)->where('is_active', true)->first();
                if (! $role || ! $condominium || (int) $role->condominium_id !== (int) $condominium->id) {
                    $validator->errors()->add("assignments.$index.role_id", 'El rol no pertenece al condominio indicado.');

                    continue;
                }
                if (! $this->user()->isPlatformAdmin() && ! in_array($role->code, ['administrador', 'directiva', 'presidente', 'tesorero', 'secretario', 'contabilidad', 'propietario', 'residente'], true)) {
                    $validator->errors()->add("assignments.$index.role_id", 'No puede asignar este rol.');
                }
            }
        }];
    }
}
