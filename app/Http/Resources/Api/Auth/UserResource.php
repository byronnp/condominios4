<?php

namespace App\Http\Resources\Api\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'document_type' => $this->whenLoaded('documentType', fn () => [
                'id' => $this->documentType?->id,
                'code' => $this->documentType?->code,
                'name' => $this->documentType?->name,
            ]),
            'document_number' => $this->document_number,
        ];
    }
}
