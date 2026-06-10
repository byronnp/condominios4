<?php

namespace App\Http\Controllers\Api\Units;

use App\Http\Controllers\Controller;
use App\Models\Condominium;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class BlockController extends Controller
{
    #[OA\Get(path: '/api/condominiums/{condominium}/blocks', operationId: 'blocksIndex', summary: 'Listar bloques del condominio', tags: ['Unidades'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Bloques encontrados')])]
    public function index(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(
            $condominium->blocks()->orderBy('sort_order')->orderBy('name')->get(),
            'Bloques encontrados.'
        );
    }

    #[OA\Post(path: '/api/condominiums/{condominium}/blocks', operationId: 'blocksStore', summary: 'Crear bloque del condominio', tags: ['Unidades'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Bloque creado')])]
    public function store(Request $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100', Rule::unique('condominium_blocks', 'code')->where('condominium_id', $condominium->id)],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $block = $condominium->blocks()->create([
            'name' => $data['name'],
            'code' => $data['code'] ?? Str::of($data['name'])->slug('_')->upper()->toString(),
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return ApiResponse::success($block, 'Bloque creado correctamente.', 201);
    }
}
