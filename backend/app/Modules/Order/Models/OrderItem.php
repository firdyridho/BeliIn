<?php

namespace App\Modules\Order\Models;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_order_id',
        'product_id',
        'product_variant_id',
        'product_name_snapshot',
        'variant_name_snapshot',
        'sku_snapshot',
        'unit_price_amount',
        'quantity',
        'discount_amount',
        'line_total_amount',
        'weight_snapshot_gram',
    ];

    protected function casts(): array
    {
        return [
            'unit_price_amount' => 'integer',
            'quantity' => 'integer',
            'discount_amount' => 'integer',
            'line_total_amount' => 'integer',
            'weight_snapshot_gram' => 'integer',
        ];
    }

    public function sellerOrder(): BelongsTo
    {
        return $this->belongsTo(SellerOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
