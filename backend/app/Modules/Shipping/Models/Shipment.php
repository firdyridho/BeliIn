<?php

namespace App\Modules\Shipping\Models;

use App\Modules\Order\Models\SellerOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_order_id',
        'shipping_provider_id',
        'shipping_service_id',
        'tracking_number',
        'status',
        'origin_snapshot_json',
        'destination_snapshot_json',
        'shipping_cost_amount',
        'buyer_shipping_amount',
        'provider_cost_amount',
        'weight_gram',
        'shipped_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'origin_snapshot_json' => 'array',
            'destination_snapshot_json' => 'array',
            'shipping_cost_amount' => 'integer',
            'buyer_shipping_amount' => 'integer',
            'provider_cost_amount' => 'integer',
            'weight_gram' => 'integer',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function sellerOrder(): BelongsTo
    {
        return $this->belongsTo(SellerOrder::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ShippingProvider::class, 'shipping_provider_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ShippingService::class, 'shipping_service_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ShipmentTrackingEvent::class)->orderBy('event_at', 'asc');
    }
}
