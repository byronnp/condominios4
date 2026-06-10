<?php

namespace App\Http\Resources\Api\Catalogs;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CatalogItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'catalog_id' => $this->catalog_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
            'metadata' => $this->metadata,
            'is_system' => $this->is_system,
            'is_active' => $this->is_active,
        ];
    }
}
