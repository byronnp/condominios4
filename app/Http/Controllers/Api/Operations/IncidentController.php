<?php

namespace App\Http\Controllers\Api\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Operations\IncidentStoreRequest;
use App\Http\Resources\Api\Operations\IncidentResource;
use App\Models\Condominium;
use App\Models\Incident;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class IncidentController extends Controller
{
    public function index(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(
            IncidentResource::collection($condominium->hasMany(Incident::class)->with(['unit', 'reportedBy'])->latest()->get()),
            'Incidentes encontrados.'
        );
    }

    public function store(IncidentStoreRequest $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validated();

        $incident = $condominium->hasMany(Incident::class)->create([
            ...$data,
            'reported_by_user_id' => $request->user()?->id,
            'category' => $data['category'] ?? 'general',
            'priority' => $data['priority'] ?? 'medium',
            'status' => 'open',
            'occurred_at' => $data['occurred_at'] ?? now(),
        ]);

        return ApiResponse::success(new IncidentResource($incident->load(['unit', 'reportedBy'])), 'Incidente registrado correctamente.', 201);
    }
}
