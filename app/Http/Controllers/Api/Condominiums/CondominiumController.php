<?php

namespace App\Http\Controllers\Api\Condominiums;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Condominiums\CondominiumStoreRequest;
use App\Http\Resources\Api\Condominiums\CondominiumResource;
use App\Models\Condominium;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class CondominiumController extends Controller
{
    #[OA\Get(path: '/api/condominiums', operationId: 'condominiumsIndex', summary: 'Listar condominios', tags: ['Condominios'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Condominios encontrados')])]
    public function index(): JsonResponse
    {
        $condominiums = Condominium::query()
            ->latest()
            ->get();

        return ApiResponse::success(CondominiumResource::collection($condominiums), 'Condominios encontrados.');
    }

    #[OA\Post(path: '/api/condominiums', operationId: 'condominiumsStore', summary: 'Crear condominio', tags: ['Condominios'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Condominio creado'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function store(CondominiumStoreRequest $request): JsonResponse
    {
        $data = $request->validated();

        $data['slug'] ??= Str::slug($data['name']);
        $data['country'] ??= 'EC';
        $data['total_units'] ??= 0;
        $data['is_active'] ??= true;

        $condominium = Condominium::create($data);

        return ApiResponse::success(new CondominiumResource($condominium), 'Condominio creado correctamente.', 201);
    }

    #[OA\Get(path: '/api/condominiums/{condominium}', operationId: 'condominiumsShow', summary: 'Obtener condominio', tags: ['Condominios'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Condominio encontrado'), new OA\Response(response: 404, description: 'No encontrado')])]
    public function show(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(
            new CondominiumResource($condominium->load(['roles.permissions', 'boards.members.user', 'paymentMethods.paymentMethodType'])),
            'Condominio encontrado.'
        );
    }
}
