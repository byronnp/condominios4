<?php

namespace App\Http\Controllers\Api\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Operations\CommonAreaStoreRequest;
use App\Http\Resources\Api\Operations\CommonAreaResource;
use App\Models\CommonArea;
use App\Models\Condominium;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CommonAreaController extends Controller
{
    public function index(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(
            CommonAreaResource::collection($condominium->hasMany(CommonArea::class)->orderBy('name')->get()),
            'Áreas comunes encontradas.'
        );
    }

    public function store(CommonAreaStoreRequest $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validated();

        $area = $condominium->hasMany(CommonArea::class)->create([
            'name' => $data['name'],
            'code' => $data['code'] ?? Str::of($data['name'])->slug('_')->toString(),
            'description' => $data['description'] ?? null,
            'capacity' => $data['capacity'] ?? 1,
            'reservation_fee' => $data['reservation_fee'] ?? 0,
            'is_reservable' => $data['is_reservable'] ?? true,
            'requires_approval' => $data['requires_approval'] ?? true,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return ApiResponse::success(new CommonAreaResource($area), 'Área común creada correctamente.', 201);
    }
}
