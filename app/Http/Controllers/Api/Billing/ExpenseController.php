<?php

namespace App\Http\Controllers\Api\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Billing\ExpenseStoreRequest;
use App\Models\Condominium;
use App\Models\Expense;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class ExpenseController extends Controller
{
    public function index(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(
            Expense::where('condominium_id', $condominium->id)->with('category')->latest()->get(),
            'Egresos encontrados.'
        );
    }

    public function store(ExpenseStoreRequest $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validated();

        $expense = Expense::create([
            ...$data,
            'condominium_id' => $condominium->id,
            'registered_by_user_id' => $request->user()?->id,
            'status' => $data['status'] ?? 'pending',
        ]);

        return ApiResponse::success($expense->load('category'), 'Egreso registrado correctamente.', 201);
    }
}
