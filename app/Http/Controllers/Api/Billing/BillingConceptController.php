<?php

namespace App\Http\Controllers\Api\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Billing\BillingConceptStoreRequest;
use App\Http\Resources\Api\Billing\BillingConceptResource;
use App\Models\BillingConcept;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class BillingConceptController extends Controller
{
    #[OA\Get(path: '/api/billing-concepts', operationId: 'billingConceptsIndex', summary: 'Listar conceptos de cobro', tags: ['Economía'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Conceptos encontrados')])]
    public function index(): JsonResponse
    {
        return ApiResponse::success(BillingConceptResource::collection(BillingConcept::orderBy('name')->get()), 'Conceptos de cobro encontrados.');
    }

    public function store(BillingConceptStoreRequest $request): JsonResponse
    {
        $data = $request->validated();

        $concept = BillingConcept::create([
            'name' => $data['name'],
            'code' => $data['code'] ?? Str::of($data['name'])->slug('_')->toString(),
            'description' => $data['description'] ?? null,
            'is_system' => false,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return ApiResponse::success(new BillingConceptResource($concept), 'Concepto de cobro creado correctamente.', 201);
    }
}
