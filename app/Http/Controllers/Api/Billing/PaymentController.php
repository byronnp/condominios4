<?php

namespace App\Http\Controllers\Api\Billing;

use App\Domain\Billing\Services\BillingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Billing\PaymentStoreRequest;
use App\Http\Resources\Api\Billing\PaymentResource;
use App\Models\Condominium;
use App\Models\Payment;
use App\Models\Unit;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(private readonly BillingService $billingService) {}

    public function index(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(
            PaymentResource::collection(Payment::where('condominium_id', $condominium->id)->with(['unit', 'user', 'allocations.monthlyFee'])->latest()->get()),
            'Pagos encontrados.'
        );
    }

    public function store(PaymentStoreRequest $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validated();

        $payment = Payment::create([
            'condominium_id' => $condominium->id,
            'unit_id' => $data['unit_id'],
            'user_id' => $data['user_id'] ?? Unit::find($data['unit_id'])?->users()->wherePivot('is_billing_responsible', true)->value('users.id'),
            'condominium_payment_method_id' => $data['condominium_payment_method_id'] ?? $condominium->paymentMethods()->where('is_default', true)->value('id'),
            'amount' => $data['amount'],
            'paid_at' => $data['paid_at'] ?? now(),
            'reference' => $data['reference'] ?? null,
            'voucher_number' => $data['voucher_number'] ?? null,
            'status' => 'confirmed',
            'notes' => $data['notes'] ?? null,
        ]);

        $payment = $this->billingService->allocatePayment($payment, $data['monthly_fee_ids'] ?? []);

        return ApiResponse::success(new PaymentResource($payment->load(['unit', 'user', 'allocations.monthlyFee'])), 'Pago registrado correctamente.', 201);
    }
}
