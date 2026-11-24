<?php

namespace App\Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'variant_name' => $this->variant?->variant_name,
            'sku' => $this->variant?->sku,
            'available_quantity' => (int) $this->available_quantity,
            'reserved_quantity' => (int) $this->reserved_quantity,
            'sold_quantity' => (int) $this->sold_quantity,
            'low_stock_threshold' => (int) $this->low_stock_threshold,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
