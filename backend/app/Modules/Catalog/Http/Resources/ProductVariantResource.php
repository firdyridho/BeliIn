<?php

namespace App\Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'variant_name' => $this->variant_name,
            'attributes' => $this->attributes_json,
            'price_amount' => (int) $this->price_amount,
            'compare_at_price_amount' => $this->compare_at_price_amount ? (int) $this->compare_at_price_amount : null,
            'status' => $this->status,
            'available_quantity' => $this->inventory ? $this->inventory->available_quantity : 0,
        ];
    }
}
