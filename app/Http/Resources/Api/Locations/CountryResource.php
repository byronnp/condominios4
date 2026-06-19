<?php

namespace App\Http\Resources\Api\Locations;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'iso3' => $this->iso3,
            'name' => $this->name,
            'phone_code' => $this->phone_code,
            'currency_code' => $this->currency_code,
            'is_active' => $this->is_active,
            'provinces' => ProvinceResource::collection($this->whenLoaded('provinces')),
        ];
    }
}
