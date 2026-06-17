<?php

namespace App\Http\Controllers\Api\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Billing\ExpenseCategoryStoreRequest;
use App\Http\Resources\Api\Billing\ExpenseCategoryResource;
use App\Models\Condominium;
use App\Models\ExpenseCategory;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ExpenseCategoryController extends Controller
{
    public function index(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(ExpenseCategoryResource::collection(ExpenseCategory::where('condominium_id', $condominium->id)->orderBy('name')->get()), 'Categorías de egreso encontradas.');
    }

    public function store(ExpenseCategoryStoreRequest $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validated();

        $category = ExpenseCategory::create([
            'condominium_id' => $condominium->id,
            'name' => $data['name'],
            'code' => $data['code'] ?? Str::of($data['name'])->slug('_')->toString(),
            'description' => $data['description'] ?? null,
            'is_active' => true,
        ]);

        return ApiResponse::success(new ExpenseCategoryResource($category), 'Categoría de egreso creada correctamente.', 201);
    }
}
