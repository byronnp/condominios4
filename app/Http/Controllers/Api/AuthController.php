<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'tenant_name' => 'required|string|max:100',
        ]);

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

        return response()->json([
            'user' => $user,
            'tenant' => $tenant,
            'token' => $user->createApiToken(),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'tenant_id' => 'required|integer|exists:tenants,id',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Credenciales inválidas.'], 401);
        }

        $tenant = Tenant::findOrFail($data['tenant_id']);

        if (! $user->belongsToTenant($tenant)) {
            return response()->json(['message' => 'El usuario no pertenece a este tenant.'], 403);
        }

        return response()->json([
            'user' => $user,
            'tenant' => $tenant,
            'token' => $user->createApiToken(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $user->revokeApiToken();
        }

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    public function user(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $user = $request->user();

        return response()->json([
            'user' => $user,
            'tenant' => $tenant,
            'roles' => $user->rolesForTenant($tenant)->pluck('name'),
        ]);
    }
}
