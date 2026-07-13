<?php

namespace App\Http\Resources\Api\PlatformAdministrators;

use App\Models\UserAccessInvitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlatformAdministratorResource extends JsonResource
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
            'access_status' => $this->accessStatus($invitation),
            'platform_role' => [
                'code' => 'administrador_senior',
                'name' => 'Administrador Senior',
            ],
            'invitation' => $this->invitationData($invitation),
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

    /**
     * @return array<string, mixed>|null
     */
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
}
