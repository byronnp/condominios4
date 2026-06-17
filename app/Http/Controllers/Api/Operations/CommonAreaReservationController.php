<?php

namespace App\Http\Controllers\Api\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Operations\CommonAreaReservationStoreRequest;
use App\Http\Resources\Api\Operations\CommonAreaReservationResource;
use App\Models\CommonArea;
use App\Models\CommonAreaReservation;
use App\Models\Condominium;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class CommonAreaReservationController extends Controller
{
    public function index(Condominium $condominium): JsonResponse
    {
        return ApiResponse::success(
            CommonAreaReservationResource::collection($condominium->hasMany(CommonAreaReservation::class)->with(['commonArea', 'unit', 'user'])->latest('starts_at')->get()),
            'Reservas encontradas.'
        );
    }

    public function store(CommonAreaReservationStoreRequest $request, Condominium $condominium): JsonResponse
    {
        $data = $request->validated();
        $area = CommonArea::where('condominium_id', $condominium->id)->findOrFail($data['common_area_id']);

        if ($this->hasOverlap((int) $data['common_area_id'], $data['starts_at'], $data['ends_at'])) {
            return ApiResponse::error('El área común ya tiene una reserva en ese horario.', 422, code: 'reservation_overlap');
        }

        $reservation = CommonAreaReservation::create([
            ...$data,
            'condominium_id' => $condominium->id,
            'user_id' => $request->user()?->id,
            'attendees_count' => $data['attendees_count'] ?? 1,
            'total_amount' => $area->reservation_fee,
            'status' => $area->requires_approval ? 'pending' : 'approved',
        ]);

        return ApiResponse::success(new CommonAreaReservationResource($reservation->load(['commonArea', 'unit', 'user'])), 'Reserva creada correctamente.', 201);
    }

    public function approve(Condominium $condominium, CommonAreaReservation $reservation): JsonResponse
    {
        $this->assertReservation($condominium, $reservation);
        $reservation->update(['status' => 'approved']);

        return ApiResponse::success(new CommonAreaReservationResource($reservation->load(['commonArea', 'unit', 'user'])), 'Reserva aprobada correctamente.');
    }

    public function cancel(Condominium $condominium, CommonAreaReservation $reservation): JsonResponse
    {
        $this->assertReservation($condominium, $reservation);
        $reservation->update(['status' => 'cancelled']);

        return ApiResponse::success(new CommonAreaReservationResource($reservation->load(['commonArea', 'unit', 'user'])), 'Reserva cancelada correctamente.');
    }

    private function hasOverlap(int $commonAreaId, string $startsAt, string $endsAt): bool
    {
        return CommonAreaReservation::where('common_area_id', $commonAreaId)
            ->whereIn('status', ['pending', 'approved'])
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();
    }

    private function assertReservation(Condominium $condominium, CommonAreaReservation $reservation): void
    {
        abort_if($reservation->condominium_id !== $condominium->id, 404);
    }
}
