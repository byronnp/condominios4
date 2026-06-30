<?php

namespace App\Http\Resources\Api\Administrators;

use App\Models\UserAccessInvitation;
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
        $invitation = $this->latestAccessInvitation;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
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
            'administrator_type' => $this->isPlatformAdmin() ? 'senior' : 'condominium',
            'access_status' => $this->accessStatus($invitation),
            'invitation' => $this->invitationData($invitation),
            'condominiums' => $this->administratorCondominiums(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function accessStatus(?UserAccessInvitation $invitation): string
    {
        if ($this->is_access_enabled) {
            return 'active';
        }

        if ($invitation?->status === UserAccessInvitation::STATUS_PENDING) {
            return $invitation->expires_at->isPast() ? 'invitation_expired' : 'pending_activation';
        }

        return match ($invitation?->status) {
            UserAccessInvitation::STATUS_EXPIRED => 'invitation_expired',
            UserAccessInvitation::STATUS_REVOKED => 'invitation_revoked',
            default => 'inactive',
        };
    }

    /** @return array<string, mixed>|null */
    private function invitationData(?UserAccessInvitation $invitation): ?array
    {
        if ($invitation === null) {
            return null;
        }

        $isExpired = $invitation->expires_at->isPast();

        return [
            'id' => $invitation->id,
            'status' => $invitation->status === UserAccessInvitation::STATUS_PENDING && $isExpired
                ? UserAccessInvitation::STATUS_EXPIRED
                : $invitation->status,
            'expires_at' => $invitation->expires_at,
            'accepted_at' => $invitation->accepted_at,
            'revoked_at' => $invitation->revoked_at,
            'is_expired' => $isExpired,
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
