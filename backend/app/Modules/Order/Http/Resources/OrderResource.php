<?php

namespace App\Modules\Order\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'buyer_id' => $this->buyer_id,
            'subtotal_amount' => (int) $this->subtotal_amount,
            'discount_amount' => (int) $this->discount_amount,
            'shipping_amount' => (int) $this->shipping_amount,
            'service_fee_amount' => (int) $this->service_fee_amount,
            'total_amount' => (int) $this->total_amount,
            'currency' => $this->currency,
            'order_status' => $this->order_status,
            'status' => $this->order_status,
            'shipping_address' => $this->shipping_address_snapshot_json,
            'payment' => $this->payment ? [
                'id' => $this->payment->id,
                'payment_reference' => $this->payment->payment_reference,
                'channel' => $this->payment->channel,
                'amount' => (int) $this->payment->amount,
                'status' => $this->payment->status,
                'expires_at' => $this->payment->expires_at?->toISOString(),
                'paid_at' => $this->payment->paid_at?->toISOString(),
            ] : null,
            'placed_at' => $this->placed_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'seller_orders' => SellerOrderResource::collection($this->whenLoaded('sellerOrders')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
