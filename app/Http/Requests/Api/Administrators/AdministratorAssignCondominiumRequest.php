<?php

namespace App\Http\Requests\Api\Administrators;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdministratorAssignCondominiumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'condominium_id' => ['required', 'integer', Rule::exists('condominiums', 'id')->whereNull('deleted_at')],
        ];
    }
}
