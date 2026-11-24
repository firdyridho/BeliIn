<?php

namespace App\Modules\Catalog\Http\Resources;

use App\Modules\Seller\Http\Resources\StoreResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'condition' => $this->condition,
            'status' => $this->status,
            'weight_gram' => $this->weight_gram,
            'dimensions' => [
                'length_mm' => $this->length_mm,
                'width_mm' => $this->width_mm,
                'height_mm' => $this->height_mm,
            ],
            'category' => new CategoryResource($this->whenLoaded('category')),
            'store' => new StoreResource($this->whenLoaded('store')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'images' => $this->images->map(fn ($img) => [
                'id' => $img->id,
                'file_path' => $img->file_path,
                'variant_id' => $img->variant_id,
            ]),
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
