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
        ];

        if (isset($this->code)) {
            $data['code'] = $this->code;
        }

        return $data;
    }
}
