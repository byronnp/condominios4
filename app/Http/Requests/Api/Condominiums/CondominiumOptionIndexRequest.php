<?php

namespace App\Http\Requests\Api\Condominiums;

use Illuminate\Foundation\Http\FormRequest;

class CondominiumOptionIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }
}
