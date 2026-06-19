<?php

namespace App\Http\Resources\Api\Locations;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'province_id' => $this->province_id,
            'code' => $this->code,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'province' => new ProvinceResource($this->whenLoaded('province')),
        ];
    }
}
