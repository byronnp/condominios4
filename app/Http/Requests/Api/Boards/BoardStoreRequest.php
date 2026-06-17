<?php

namespace App\Http\Requests\Api\Boards;

use Illuminate\Foundation\Http\FormRequest;

class BoardStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'members' => ['nullable', 'array'],
            'members.*.user_id' => ['required_with:members', 'integer', 'exists:users,id'],
            'members.*.role_name' => ['required_with:members', 'string', 'max:100'],
            'members.*.started_at' => ['required_with:members', 'date'],
            'members.*.ended_at' => ['nullable', 'date', 'after_or_equal:members.*.started_at'],
        ];
    }
}
