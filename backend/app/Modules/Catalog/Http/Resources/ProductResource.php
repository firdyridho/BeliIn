<?php

namespace App\Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $minPrice = $this->variants->min('price_amount') ?? 0;
        $maxPrice = $this->variants->max('price_amount') ?? 0;

        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'store_name' => $this->store?->name,
            'store_slug' => $this->store?->slug,
            'category_id' => $this->category_id,
            'category_name' => $this->category?->name,
            'name' => $this->name,
            'slug' => $this->slug,
            'condition' => $this->condition,
            'weight_gram' => $this->weight_gram,
            'min_price' => (int) $minPrice,
            'max_price' => (int) $maxPrice,
            'primary_image' => $this->images->first()?->file_path,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
