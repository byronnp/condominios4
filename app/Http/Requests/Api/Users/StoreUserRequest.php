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
        if ($this->user()?->isPlatformAdmin()) {
            return;
        }

        $condominiumId = $this->user()?->manageableCondominiumIds('users.create')[0] ?? null;
        $assignments = collect($this->input('assignments', []))->map(fn ($assignment) => [
            ...(array) $assignment,
            'condominium_id' => $condominiumId,
        ])->all();
        $this->merge(['assignments' => $assignments]);
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
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.condominium_id' => ['nullable', 'integer', 'distinct'],
            'assignments.*.role_id' => ['required', 'integer', 'distinct'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ($this->input('assignments', []) as $index => $assignment) {
                $role = Role::query()->whereKey($assignment['role_id'] ?? null)->where('is_active', true)->first();
                $condominiumId = isset($assignment['condominium_id']) ? (int) $assignment['condominium_id'] : null;
                if (! $role || $role->condominium_id !== $condominiumId) {
                    $validator->errors()->add("assignments.$index.role_id", 'El rol no pertenece al condominio indicado.');

                    continue;
                }
                if (! $this->user()->isPlatformAdmin() && ! in_array($role->code, ['administrador', 'directiva', 'presidente', 'tesorero', 'secretario', 'contabilidad', 'propietario', 'residente'], true)) {
                    $validator->errors()->add("assignments.$index.role_id", 'No puede asignar este rol.');
                }
                if (! $this->user()->isPlatformAdmin() && $role->condominium_id === null) {
                    $validator->errors()->add("assignments.$index.role_id", 'No puede asignar roles globales.');
                }
            }
        }];
    }
}
