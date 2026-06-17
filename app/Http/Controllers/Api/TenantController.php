<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Tenants\TenantStoreRequest;
use App\Http\Resources\Api\Roles\RoleResource;
use App\Http\Resources\Api\Tenants\TenantResource;
use App\Models\Role;
use App\Models\Tenant;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function index(): JsonResponse
    {
        $tenants = Tenant::withCount('users')->get();

        return ApiResponse::success(TenantResource::collection($tenants), 'Tenants encontrados.');
    }

    public function store(TenantStoreRequest $request): JsonResponse
    {
        $data = $request->validated();

        $tenant = Tenant::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
        ]);

        return ApiResponse::success(new TenantResource($tenant), 'Tenant creado correctamente.', 201);
    }

    public function roles(): JsonResponse
    {
        return ApiResponse::success(RoleResource::collection(Role::all()), 'Roles encontrados.');
    }
}
