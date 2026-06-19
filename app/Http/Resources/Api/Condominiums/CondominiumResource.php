<?php

namespace App\Http\Resources\Api\Condominiums;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CondominiumResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'ruc' => $this->ruc,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
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
            'total_units' => $this->total_units,
            'is_active' => $this->is_active,
        ];

        if (isset($this->code)) {
            $data['code'] = $this->code;
        }

        return $data;
    }
}
