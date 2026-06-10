<?php

namespace App\Http\Controllers\Api\Boards;

use App\Http\Controllers\Controller;
use App\Models\Condominium;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class BoardController extends Controller
{
    #[OA\Get(path: '/api/condominiums/{condominium}/boards', operationId: 'boardsIndex', summary: 'Listar directivas por condominio', tags: ['Directivas'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Directivas encontradas')])]
    public function index(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(
            $condominium->boards()->with('members.user')->latest('start_date')->get(),
            'Directivas encontradas.'
        );
    }

    #[OA\Post(path: '/api/condominiums/{condominium}/boards', operationId: 'boardsStore', summary: 'Crear directiva', tags: ['Directivas'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Directiva creada'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function store(Request $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'members' => ['nullable', 'array'],
            'members.*.user_id' => ['required_with:members', 'integer', 'exists:users,id'],
            'members.*.role_name' => ['required_with:members', 'string', 'max:100'],
            'members.*.started_at' => ['required_with:members', 'date'],
            'members.*.ended_at' => ['nullable', 'date', 'after_or_equal:members.*.started_at'],
        ]);

        if (($data['is_active'] ?? true) === true) {
            $condominium->boards()->where('is_active', true)->update(['is_active' => false]);
        }

        $board = $condominium->boards()->create([
            'name' => $data['name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'notes' => $data['notes'] ?? null,
        ]);

        foreach ($data['members'] ?? [] as $member) {
            $board->members()->create([
                'user_id' => $member['user_id'],
                'role_name' => $member['role_name'],
                'started_at' => $member['started_at'],
                'ended_at' => $member['ended_at'] ?? null,
                'is_active' => true,
            ]);
        }

        return ApiResponse::success($board->load('members.user'), 'Directiva creada correctamente.', 201);
    }
}
