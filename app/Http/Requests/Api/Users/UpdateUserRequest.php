<?php

namespace App\Http\Requests\Api\Users;

use App\Models\Role;
use App\Rules\ValidCatalogItem;
use App\Rules\ValidDocumentNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $target = $this->route('user');
        if ($this->has('document_number') && ! $this->has('document_type_id')) {
            $this->merge(['document_type_id' => $target?->document_type_id]);
        }

        if ($this->user()?->isPlatformAdmin() || ! $this->has('assignments')) {
            return;
        }

        $allowedIds = $this->user()?->manageableCondominiumIds('users.update') ?? [];
        $condominiumId = $target?->condominiums()->wherePivot('is_active', true)->wherePivotNull('deleted_at')
            ->whereIn('condominiums.id', $allowedIds)->value('condominiums.id');
        $this->merge(['assignments' => collect($this->input('assignments', []))->map(fn ($assignment) => [
            ...(array) $assignment,
            'condominium_id' => $condominiumId,
        ])->all()]);
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'country' => ['sometimes', 'required', 'string', 'size:2', Rule::exists('countries', 'code')->where('is_active', true)],
            'document_type_id' => ['sometimes', 'required', 'integer', new ValidCatalogItem('document_types')],
            'document_number' => ['sometimes', 'required', 'string', 'max:30', new ValidDocumentNumber, Rule::unique('users', 'document_number')->where(fn ($query) => $query->where('country', $this->input('country', $user?->country))->where('document_type_id', $this->input('document_type_id', $user?->document_type_id)))->ignore($user?->id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'secondary_phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'assignments' => ['sometimes', 'array', 'min:1'],
            'assignments.*.condominium_id' => ['nullable', 'integer', 'distinct'],
            'assignments.*.role_id' => ['required_with:assignments', 'integer', 'distinct'],
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
            }
        }];
    }
}
