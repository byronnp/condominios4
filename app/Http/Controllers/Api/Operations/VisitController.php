<?php

namespace App\Http\Controllers\Api\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Operations\VisitLogRequest;
use App\Http\Requests\Api\Operations\VisitStoreRequest;
use App\Http\Resources\Api\Operations\VisitLogResource;
use App\Http\Resources\Api\Operations\VisitResource;
use App\Models\Condominium;
use App\Models\Visit;
use App\Models\VisitLog;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class VisitController extends Controller
{
    public function index(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(
            VisitResource::collection($condominium->hasMany(Visit::class)->with(['unit', 'visitor', 'logs'])->latest()->get()),
            'Visitas encontradas.'
        );
    }

    public function store(VisitStoreRequest $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validated();
        $status = $data['status'] ?? 'pending';

        $visit = $condominium->hasMany(Visit::class)->create([
            ...$data,
            'registered_by_user_id' => $request->user()?->id,
            'authorized_by_user_id' => $status === 'authorized' ? $request->user()?->id : null,
            'authorization_code' => Str::upper(Str::random(10)),
            'status' => $status,
        ]);

        return ApiResponse::success(new VisitResource($visit->load(['unit', 'visitor', 'logs'])), 'Visita registrada correctamente.', 201);
    }

    public function authorizeVisit(Condominium $condominium, Visit $visit): JsonResponse
    {
        $this->assertVisit($condominium, $visit);

        $visit->update([
            'authorized_by_user_id' => request()->user()?->id,
            'status' => 'authorized',
        ]);

        return ApiResponse::success(new VisitResource($visit->load(['unit', 'visitor', 'logs'])), 'Visita autorizada correctamente.');
    }

    public function entry(VisitLogRequest $request, Condominium $condominium, Visit $visit): JsonResponse
    {
        return $this->log($request, $condominium, $visit, 'entry', 'Ingreso registrado correctamente.');
    }

    public function exit(VisitLogRequest $request, Condominium $condominium, Visit $visit): JsonResponse
    {
        return $this->log($request, $condominium, $visit, 'exit', 'Salida registrada correctamente.');
    }

    private function log(VisitLogRequest $request, Condominium $condominium, Visit $visit, string $type, string $message): JsonResponse
    {
        $this->assertVisit($condominium, $visit);
        abort_if(! in_array($visit->status, ['authorized', 'in_progress'], true), 422, 'La visita debe estar autorizada para registrar accesos.');

        $data = $request->validated();
        $log = VisitLog::create([
            'condominium_id' => $condominium->id,
            'unit_id' => $visit->unit_id,
            'visit_id' => $visit->id,
            'visitor_id' => $visit->visitor_id,
            'logged_by_user_id' => $request->user()?->id,
            'type' => $type,
            'logged_at' => $data['logged_at'] ?? now(),
            'notes' => $data['notes'] ?? null,
        ]);

        $visit->update(['status' => $type === 'entry' ? 'in_progress' : 'completed']);

        return ApiResponse::success(new VisitLogResource($log->load('visitor')), $message, 201);
    }

    private function assertVisit(Condominium $condominium, Visit $visit): void
    {
        abort_if($visit->condominium_id !== $condominium->id, 404);
    }
}
