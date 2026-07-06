<?php

namespace App\Http\Requests\Api\Auth;

use App\Domain\Auth\Services\JwtTokenService;
use App\Models\AuthSession;
use App\Models\User;
use App\Rules\ValidCatalogItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Throwable;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (app()->environment(['local', 'testing'])) {
            return true;
        }

        $token = $this->bearerToken();

        if (! $token) {
            return false;
        }

        try {
            $jwtTokenService = app(JwtTokenService::class);
            $payload = $jwtTokenService->decode($token);

            if ($jwtTokenService->isRevoked($payload->jti ?? '')) {
                return false;
            }
        } catch (Throwable) {
            return false;
        }

        $user = User::find((int) ($payload->sub ?? 0));

        if (! $user || ! $user->isPlatformAdmin() || ! $user->is_access_enabled) {
            return false;
        }

        if ($this->userIsInactive($user)) {
            return false;
        }

        if (! isset($payload->auth_session_id)) {
            return false;
        }

        return AuthSession::query()
            ->active()
            ->whereKey((int) $payload->auth_session_id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'required_without:first_name', 'string', 'max:255'],
            'first_name' => ['nullable', 'required_without:name', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'country' => ['required', 'string', 'size:2'],
            'document_type_id' => ['required', 'integer', new ValidCatalogItem('document_types')],
            'document_number' => [
                'required',
                'string',
                'max:30',
                Rule::unique('users', 'document_number')->where(fn ($query) => $query
                    ->where('country', $this->input('country'))
                    ->where('document_type_id', $this->input('document_type_id'))
                    ->whereNull('deleted_at')),
            ],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required_without' => 'Debes enviar el nombre completo o los nombres.',
            'name.string' => 'El nombre completo debe ser una cadena de texto.',
            'name.max' => 'El nombre completo no puede superar los 255 caracteres.',
            'first_name.required_without' => 'Debes enviar los nombres o el nombre completo.',
            'first_name.string' => 'Los nombres deben ser una cadena de texto.',
            'first_name.max' => 'Los nombres no pueden superar los 255 caracteres.',
            'last_name.string' => 'Los apellidos deben ser una cadena de texto.',
            'last_name.max' => 'Los apellidos no pueden superar los 255 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.string' => 'El correo electrónico debe ser una cadena de texto.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.max' => 'El correo electrónico no puede superar los 255 caracteres.',
            'email.unique' => 'El correo electrónico ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña debe ser una cadena de texto.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'country.required' => 'El país es obligatorio.',
            'country.string' => 'El país debe ser una cadena de texto.',
            'country.size' => 'El país debe tener exactamente 2 caracteres.',
            'document_type_id.required' => 'El tipo de documento es obligatorio.',
            'document_type_id.integer' => 'El tipo de documento debe ser un entero.',
            'document_number.required' => 'El número de documento es obligatorio.',
            'document_number.string' => 'El número de documento debe ser una cadena de texto.',
            'document_number.max' => 'El número de documento no puede superar los 30 caracteres.',
            'document_number.unique' => 'El número de documento ya está registrado para el país y tipo de documento seleccionados.',
            'device_name.string' => 'El nombre del dispositivo debe ser una cadena de texto.',
            'device_name.max' => 'El nombre del dispositivo no puede superar los 255 caracteres.',
        ];
    }

    private function userIsInactive(User $user): bool
    {
        if (! Schema::hasColumn($user->getTable(), 'is_active')) {
            return false;
        }

        return ! (bool) $user->getAttribute('is_active');
    }
}
