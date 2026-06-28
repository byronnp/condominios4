<?php

namespace App\Http\Requests\Api\Users;

use Illuminate\Foundation\Http\FormRequest;

class UserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_active' => ['required_without:is_access_enabled', 'boolean'],
            'is_access_enabled' => ['required_without:is_active', 'boolean'],
        ];
    }

    public function status(): bool
    {
        return $this->boolean($this->has('is_active') ? 'is_active' : 'is_access_enabled');
    }
}
