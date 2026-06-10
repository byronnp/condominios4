<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class HealthController extends Controller
{
    #[OA\Get(
        path: '/api/health',
        operationId: 'systemHealth',
        summary: 'Verificar estado de la API',
        tags: ['Sistema'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'API operativa',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'API operativa.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'status', type: 'string', example: 'ok'),
                                new OA\Property(property: 'timestamp', type: 'string', format: 'date-time'),
                            ],
                            type: 'object'
                        ),
                        new OA\Property(property: 'meta', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
        ], 'API operativa.');
    }
}
