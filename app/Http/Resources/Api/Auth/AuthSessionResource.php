<?php

namespace App\Http\Resources\Api\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'started_at' => $this->started_at?->toISOString(),
            'last_activity_at' => $this->last_activity_at?->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'device_name' => $this->device_name,
            'login_method' => $this->login_method,
            'logout_reason' => $this->logout_reason,
            'is_active' => $this->is_active,
        ];
    }
}
