<?php

namespace App\Http\Requests\Api\PlatformAdministrators;

use Illuminate\Foundation\Http\FormRequest;

class PlatformAdministratorStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_access_enabled' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'is_access_enabled.required' => 'El estado de acceso es obligatorio.',
            'is_access_enabled.boolean' => 'El estado de acceso debe ser verdadero o falso.',
        ];
    }
}
