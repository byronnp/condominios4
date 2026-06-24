<?php

namespace App\Http\Requests\Api\Administrators;

use Illuminate\Foundation\Http\FormRequest;

class AdministratorStatusRequest extends FormRequest
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
}
