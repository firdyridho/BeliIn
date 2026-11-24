<?php

namespace App\Modules\Order\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'seller_order_number' => $this->seller_order_number,
            'store_id' => $this->store_id,
            'store_name' => $this->store?->name,
            'subtotal_amount' => (int) $this->subtotal_amount,
            'discount_amount' => (int) $this->discount_amount,
            'shipping_amount' => (int) $this->shipping_amount,
            'seller_status' => $this->seller_status,
            'status' => $this->seller_status,
            'total_amount' => (int) ($this->subtotal_amount + $this->shipping_amount - $this->discount_amount),
            'shipment' => $this->shipment ? [
                'id' => $this->shipment->id,
                'tracking_number' => $this->shipment->tracking_number,
                'status' => $this->shipment->status,
                'shipping_cost' => (int) $this->shipment->shipping_cost,
                'provider' => $this->shipment->provider ? [
                    'id' => $this->shipment->provider->id,
                    'code' => $this->shipment->provider->code,
                    'name' => $this->shipment->provider->name,
                ] : null,
                'service' => $this->shipment->service ? [
                    'id' => $this->shipment->service->id,
                    'service_code' => $this->shipment->service->service_code,
                    'service_name' => $this->shipment->service->service_name,
                ] : null,
                'tracking_events' => $this->shipment->trackingEvents->map(fn ($ev) => [
                    'id' => $ev->id,
                    'status' => $ev->status,
                    'location' => $ev->location,
                    'description' => $ev->description,
                    'event_time' => $ev->event_time?->toISOString(),
                ]),
            ] : null,
            'items' => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->product_variant_id,
                'name' => $item->product_name_snapshot,
                'variant_name' => $item->variant_name_snapshot,
                'sku' => $item->sku_snapshot,
                'unit_price' => (int) $item->unit_price_amount,
                'quantity' => (int) $item->quantity,
                'line_total' => (int) $item->line_total_amount,
            ]),
            'accepted_at' => $this->accepted_at?->toISOString(),
            'shipped_at' => $this->shipped_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
