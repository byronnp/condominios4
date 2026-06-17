<?php

namespace App\Http\Requests\Api\Operations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaintenanceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $condominium = $this->route('condominium');

        return [
            'common_area_id' => ['nullable', 'integer', Rule::exists('common_areas', 'id')->where('condominium_id', $condominium?->id)],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', Rule::in(['preventive', 'corrective'])],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'critical'])],
            'scheduled_starts_at' => ['nullable', 'date'],
            'scheduled_ends_at' => ['nullable', 'date', 'after_or_equal:scheduled_starts_at'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'tasks' => ['nullable', 'array'],
            'tasks.*.title' => ['required_with:tasks', 'string', 'max:255'],
            'tasks.*.description' => ['nullable', 'string'],
            'tasks.*.assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'tasks.*.due_at' => ['nullable', 'date'],
        ];
    }
}
