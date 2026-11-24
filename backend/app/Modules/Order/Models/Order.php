<?php

namespace App\Modules\Order\Models;

use App\Models\User;
use App\Modules\Payment\Models\Payment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'buyer_id',
        'shipping_address_snapshot_json',
        'subtotal_amount',
        'discount_amount',
        'shipping_amount',
        'service_fee_amount',
        'total_amount',
        'currency',
        'order_status',
        'placed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'shipping_address_snapshot_json' => 'array',
            'subtotal_amount' => 'integer',
            'discount_amount' => 'integer',
            'shipping_amount' => 'integer',
            'service_fee_amount' => 'integer',
            'total_amount' => 'integer',
            'placed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function sellerOrders(): HasMany
    {
        return $this->hasMany(SellerOrder::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
