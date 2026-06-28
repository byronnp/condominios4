<?php

namespace App\Http\Resources\Api\Users;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'country' => $this->country,
            'document_type' => $this->whenLoaded('documentType', fn () => $this->documentType ? [
                'id' => $this->documentType->id, 'code' => $this->documentType->code, 'name' => $this->documentType->name,
            ] : null),
            'document_number' => $this->document_number,
            'phone' => $this->phone,
            'secondary_phone' => $this->secondary_phone,
            'status' => $this->is_access_enabled ? 'active' : 'inactive',
            'is_access_enabled' => (bool) $this->is_access_enabled,
            'assignments' => $this->assignments($request),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function assignments(Request $request): array
    {
        $actor = $request->user();
        $isPlatformAdmin = $actor?->isPlatformAdmin() ?? false;
        $visibleCondominiumIds = $isPlatformAdmin ? null : ($actor?->manageableCondominiumIds() ?? []);

        $condominiums = DB::table('condominium_user')
            ->join('condominiums', 'condominiums.id', '=', 'condominium_user.condominium_id')
            ->join('condominium_user_role', 'condominium_user_role.condominium_user_id', '=', 'condominium_user.id')
            ->join('roles', 'roles.id', '=', 'condominium_user_role.role_id')
            ->where('condominium_user.user_id', $this->id)->whereNull('condominium_user.deleted_at')
            ->whereNull('condominium_user_role.deleted_at')->whereNull('roles.deleted_at')
            ->when($visibleCondominiumIds !== null, fn ($query) => $query->whereIn('condominium_user.condominium_id', $visibleCondominiumIds))
            ->get(['condominiums.id as condominium_id', 'condominiums.name as condominium_name', 'condominium_user.is_active', 'roles.id as role_id', 'roles.code as role_code', 'roles.name as role_name'])
            ->map(fn ($row): array => (array) $row);

        $global = $isPlatformAdmin
            ? DB::table('role_user')->join('roles', 'roles.id', '=', 'role_user.role_id')
                ->where('role_user.user_id', $this->id)->whereNull('roles.condominium_id')->whereNull('roles.deleted_at')
                ->get(['roles.id as role_id', 'roles.code as role_code', 'roles.name as role_name'])
                ->map(fn ($row): array => ['condominium_id' => null, 'condominium_name' => null, 'is_active' => true, ...(array) $row])
            : collect();

        return $global->concat($condominiums)->values()->all();
    }
}
