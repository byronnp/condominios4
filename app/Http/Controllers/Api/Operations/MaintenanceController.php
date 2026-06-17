<?php

namespace App\Http\Controllers\Api\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Operations\MaintenanceStoreRequest;
use App\Http\Requests\Api\Operations\MaintenanceTaskStoreRequest;
use App\Http\Resources\Api\Operations\MaintenanceResource;
use App\Http\Resources\Api\Operations\MaintenanceTaskResource;
use App\Models\Condominium;
use App\Models\Maintenance;
use App\Models\MaintenanceTask;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MaintenanceController extends Controller
{
    public function index(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(
            MaintenanceResource::collection($condominium->hasMany(Maintenance::class)->with(['commonArea', 'tasks'])->latest()->get()),
            'Mantenimientos encontrados.'
        );
    }

    public function store(MaintenanceStoreRequest $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validated();

        $maintenance = DB::transaction(function () use ($condominium, $data, $request): Maintenance {
            $maintenance = $condominium->hasMany(Maintenance::class)->create([
                'common_area_id' => $data['common_area_id'] ?? null,
                'reported_by_user_id' => $request->user()?->id,
                'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'] ?? 'corrective',
                'priority' => $data['priority'] ?? 'medium',
                'status' => 'scheduled',
                'scheduled_starts_at' => $data['scheduled_starts_at'] ?? null,
                'scheduled_ends_at' => $data['scheduled_ends_at'] ?? null,
                'cost' => $data['cost'] ?? null,
            ]);

            foreach ($data['tasks'] ?? [] as $task) {
                $maintenance->tasks()->create([
                    'title' => $task['title'],
                    'description' => $task['description'] ?? null,
                    'assigned_to_user_id' => $task['assigned_to_user_id'] ?? null,
                    'due_at' => $task['due_at'] ?? null,
                    'status' => 'pending',
                ]);
            }

            return $maintenance;
        });

        return ApiResponse::success(new MaintenanceResource($maintenance->load(['commonArea', 'tasks'])), 'Mantenimiento registrado correctamente.', 201);
    }

    public function storeTask(MaintenanceTaskStoreRequest $request, Condominium $condominium, Maintenance $maintenance): JsonResponse
    {
        abort_if($maintenance->condominium_id !== $condominium->id, 404);

        $data = $request->validated();
        $task = $maintenance->tasks()->create([
            ...$data,
            'status' => 'pending',
        ]);

        return ApiResponse::success(new MaintenanceTaskResource($task), 'Tarea de mantenimiento creada correctamente.', 201);
    }

    public function completeTask(Condominium $condominium, Maintenance $maintenance, MaintenanceTask $task): JsonResponse
    {
        abort_if($maintenance->condominium_id !== $condominium->id || $task->maintenance_id !== $maintenance->id, 404);

        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        if (! $maintenance->tasks()->where('status', '!=', 'completed')->exists()) {
            $maintenance->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        return ApiResponse::success(new MaintenanceTaskResource($task), 'Tarea de mantenimiento completada correctamente.');
    }
}
