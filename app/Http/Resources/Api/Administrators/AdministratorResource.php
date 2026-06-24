<?php

namespace App\Http\Resources\Api\Administrators;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class AdministratorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'country' => $this->country,
            'document_type' => $this->whenLoaded('documentType', fn () => $this->documentType ? [
                'id' => $this->documentType->id,
                'code' => $this->documentType->code,
                'name' => $this->documentType->name,
            ] : null),
            'document_number' => $this->document_number,
            'phone' => $this->phone,
            'secondary_phone' => $this->secondary_phone,
            'is_access_enabled' => (bool) $this->is_access_enabled,
            'condominiums' => $this->administratorCondominiums(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function administratorCondominiums(): array
    {
        return DB::table('condominium_user')
            ->join('condominiums', 'condominiums.id', '=', 'condominium_user.condominium_id')
            ->join('condominium_user_role', 'condominium_user_role.condominium_user_id', '=', 'condominium_user.id')
            ->join('roles', 'roles.id', '=', 'condominium_user_role.role_id')
            ->where('condominium_user.user_id', $this->id)
            ->where('roles.code', 'administrador')
            ->whereNull('condominium_user.deleted_at')
            ->whereNull('condominium_user_role.deleted_at')
            ->whereNull('roles.deleted_at')
            ->whereNull('condominiums.deleted_at')
            ->orderBy('condominiums.name')
            ->get([
                'condominiums.id',
                'condominiums.name',
                'condominiums.slug',
                'condominium_user.is_active',
                'condominium_user.joined_at',
            ])
            ->map(fn ($condominium): array => [
                'id' => $condominium->id,
                'name' => $condominium->name,
                'slug' => $condominium->slug,
                'is_active' => (bool) $condominium->is_active,
                'joined_at' => $condominium->joined_at,
            ])
            ->all();
    }
}
