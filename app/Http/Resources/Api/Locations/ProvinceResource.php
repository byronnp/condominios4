<?php

namespace App\Http\Resources\Api\Locations;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProvinceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'country_id' => $this->country_id,
            'code' => $this->code,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'country' => new CountryResource($this->whenLoaded('country')),
            'cities' => CityResource::collection($this->whenLoaded('cities')),
        ];
    }
}
