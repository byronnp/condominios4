<?php

namespace App\Http\Controllers\Api\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Billing\ExtraordinaryFeeStoreRequest;
use App\Models\BillingConcept;
use App\Models\Condominium;
use App\Models\ExtraordinaryFee;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ExtraordinaryFeeController extends Controller
{
    public function index(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success($condominium->hasMany(ExtraordinaryFee::class)->with('units')->get(), 'Cuotas extraordinarias encontradas.');
    }

    public function store(ExtraordinaryFeeStoreRequest $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validated();

        $concept = BillingConcept::where('code', 'cuota_extraordinaria')->firstOrFail();

        $fee = DB::transaction(function () use ($condominium, $data, $concept) {
            $fee = $condominium->hasMany(ExtraordinaryFee::class)->create([
                'billing_concept_id' => $concept->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'amount' => $data['amount'],
                'starts_on' => $data['starts_on'],
                'ends_on' => $data['ends_on'],
                'apply_to' => $data['apply_to'],
                'is_active' => true,
            ]);

            if ($data['apply_to'] === 'selected_units') {
                $fee->units()->sync($data['unit_ids'] ?? []);
            }

            return $fee;
        });

        return ApiResponse::success($fee->load('units'), 'Cuota extraordinaria creada correctamente.', 201);
    }
}
