<?php

namespace App\Http\Controllers\Api\Boards;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Boards\BoardStoreRequest;
use App\Http\Resources\Api\Boards\BoardResource;
use App\Models\Condominium;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class BoardController extends Controller
{
    #[OA\Get(path: '/api/condominiums/{condominium}/boards', operationId: 'boardsIndex', summary: 'Listar directivas por condominio', tags: ['Directivas'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Directivas encontradas')])]
    public function index(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(
            BoardResource::collection($condominium->boards()->with('members.user')->latest('start_date')->get()),
            'Directivas encontradas.'
        );
    }

    #[OA\Post(path: '/api/condominiums/{condominium}/boards', operationId: 'boardsStore', summary: 'Crear directiva', tags: ['Directivas'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Directiva creada'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function store(BoardStoreRequest $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validated();

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

        return ApiResponse::success(new BoardResource($board->load('members.user')), 'Directiva creada correctamente.', 201);
    }
}
