<?php

namespace App\Http\Controllers\Api\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Operations\VisitorStoreRequest;
use App\Http\Resources\Api\Operations\VisitorResource;
use App\Models\Condominium;
use App\Models\Visitor;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class VisitorController extends Controller
{
    public function index(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(
            VisitorResource::collection($condominium->hasMany(Visitor::class)->with('documentType')->latest()->get()),
            'Visitantes encontrados.'
        );
    }

    public function store(VisitorStoreRequest $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validated();

        $visitor = $condominium->hasMany(Visitor::class)->create([
            ...$data,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return ApiResponse::success(new VisitorResource($visitor->load('documentType')), 'Visitante registrado correctamente.', 201);
    }
}
