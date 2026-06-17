<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LegacyLoginRequest;
use App\Http\Requests\Api\Auth\LegacyRegisterRequest;
use App\Http\Resources\Api\Auth\UserResource;
use App\Http\Resources\Api\Tenants\TenantResource;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(LegacyRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $tenant = Tenant::firstOrCreate([
            'slug' => Str::slug($data['tenant_name']),
        ], [
            'name' => $data['tenant_name'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $user->tenants()->attach($tenant);
        $user->assignRole('admin', $tenant);

        return ApiResponse::success([
            'user' => new UserResource($user),
            'tenant' => new TenantResource($tenant),
            'token' => $user->createApiToken(),
        ], 'Usuario registrado correctamente.', 201);
    }

    public function login(LegacyLoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return ApiResponse::error('Credenciales inválidas.', 401, code: 'invalid_credentials');
        }

        $tenant = Tenant::findOrFail($data['tenant_id']);

        if (! $user->belongsToTenant($tenant)) {
            return ApiResponse::error('El usuario no pertenece a este tenant.', 403, code: 'tenant_forbidden');
        }

        return ApiResponse::success([
            'user' => new UserResource($user),
            'tenant' => new TenantResource($tenant),
            'token' => $user->createApiToken(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $user->revokeApiToken();
        }

        return ApiResponse::success(message: 'Sesión cerrada correctamente.');
    }

    public function user(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $user = $request->user();

        return ApiResponse::success([
            'user' => new UserResource($user),
            'tenant' => new TenantResource($tenant),
            'roles' => $user->rolesForTenant($tenant)->pluck('name'),
        ]);
    }
}
