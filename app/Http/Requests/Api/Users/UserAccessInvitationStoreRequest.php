<?php

namespace App\Http\Requests\Api\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserAccessInvitationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)->whereNull('deleted_at')],
        ];
    }
}
