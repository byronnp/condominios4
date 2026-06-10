<?php

namespace App\Support\Api;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Builds the standard successful API response payload.
     *
     * @param  array<string, mixed>  $meta
     */
    public static function success(mixed $data = null, string $message = 'Operación realizada correctamente.', int $status = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => (object) $meta,
        ], $status);
    }

    /**
     * Builds the standard failed API response payload.
     *
     * @param  array<string, mixed>|null  $errors
     * @param  array<string, mixed>  $meta
     */
    public static function error(string $message, int $status = 400, ?array $errors = null, ?string $code = null, array $meta = []): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
            'errors' => $errors ?? (object) [],
            'meta' => (object) $meta,
        ];

        if ($code !== null) {
            $payload['code'] = $code;
        }

        return response()->json($payload, $status);
    }
}
