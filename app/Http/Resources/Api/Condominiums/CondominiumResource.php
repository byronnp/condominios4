<?php

namespace App\Http\Resources\Api\Condominiums;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CondominiumResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'ruc' => $this->ruc,
            'type' => $this->whenLoaded('type', fn () => $this->type ? [
                'id' => $this->type->id,
                'code' => $this->type->code,
                'name' => $this->type->name,
            ] : null),
            'description' => $this->description,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'address_reference' => $this->address_reference,
            'country_code' => $this->country_code,
            'province_id' => $this->province_id,
            'city_id' => $this->city_id,
            'country' => $this->whenLoaded('country', fn () => $this->country ? [
                'id' => $this->country->id,
                'code' => $this->country->code,
                'name' => $this->country->name,
            ] : null),
            'province' => $this->whenLoaded('province', fn () => $this->province ? [
                'id' => $this->province->id,
                'code' => $this->province->code,
                'name' => $this->province->name,
            ] : null),
            'city' => $this->whenLoaded('city', fn () => $this->city ? [
                'id' => $this->city->id,
                'code' => $this->city->code,
                'name' => $this->city->name,
            ] : null),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'currency' => $this->whenLoaded('activeBillingSetting', fn () => $this->activeBillingSetting?->currency),
            'towers_count' => $this->towers_count,
            'houses_count' => $this->houses_count,
            'total_units' => $this->total_units,
            'features' => $this->whenLoaded('features', fn () => $this->features->map(fn ($feature) => [
                'id' => $feature->id,
                'code' => $feature->code,
                'name' => $feature->name,
            ])->values()),
            'administrator' => $this->when(
                $this->relationLoaded('users') && $this->relationLoaded('roles'),
                fn () => $this->administrator(),
            ),
            'logo_path' => $this->logo_path,
            'logo_url' => $this->logo_path ? Storage::disk('s3')->url($this->logo_path) : null,
            'is_active' => $this->is_active,
        ];

        if (isset($this->code)) {
            $data['code'] = $this->code;
        }

        return $data;
    }

    /**
     * Return the first user assigned to the condominium administrator role.
     *
     * @return array<string, mixed>|null
     */
    private function administrator(): ?array
    {
        $administratorRole = $this->roles->firstWhere('code', 'administrador');

        if ($administratorRole === null) {
            return null;
        }

        $condominiumUserIds = $this->users
            ->pluck('pivot.id')
            ->filter()
            ->values();

        if ($condominiumUserIds->isEmpty()) {
            return null;
        }

        $administratorCondominiumUserId = DB::table('condominium_user_role')
            ->whereIn('condominium_user_id', $condominiumUserIds)
            ->where('role_id', $administratorRole->id)
            ->whereNull('deleted_at')
            ->value('condominium_user_id');

        if ($administratorCondominiumUserId === null) {
            return null;
        }

        /** @var User|null $administrator */
        $administrator = $this->users
            ->first(fn (User $user): bool => (int) $user->pivot->id === (int) $administratorCondominiumUserId);

        if ($administrator === null) {
            return null;
        }

        return [
            'id' => $administrator->id,
            'name' => $administrator->name,
            'email' => $administrator->email,
            'document_type' => $administrator->relationLoaded('documentType') && $administrator->documentType ? [
                'id' => $administrator->documentType->id,
                'code' => $administrator->documentType->code,
                'name' => $administrator->documentType->name,
            ] : null,
            'document_number' => $administrator->document_number,
            'phone' => $administrator->phone,
            'is_access_enabled' => $administrator->is_access_enabled,
            'is_active' => (bool) $administrator->pivot->is_active,
        ];
    }
}
