<?php

namespace App\Http\Requests\Api\Operations;

use Illuminate\Foundation\Http\FormRequest;

class VisitLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'logged_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
