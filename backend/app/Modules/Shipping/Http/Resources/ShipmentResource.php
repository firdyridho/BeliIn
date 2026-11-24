<?php

namespace App\Modules\Shipping\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tracking_number' => $this->tracking_number,
            'provider_name' => $this->provider?->name,
            'service_name' => $this->service?->service_name,
            'status' => $this->status,
            'origin' => $this->origin_snapshot_json,
            'destination' => $this->destination_snapshot_json,
            'shipping_cost' => (int) $this->shipping_cost_amount,
            'weight_gram' => (int) $this->weight_gram,
            'shipped_at' => $this->shipped_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'events' => $this->events->map(fn ($e) => [
                'event_code' => $e->event_code,
                'description' => $e->event_description,
                'location' => $e->location,
                'event_at' => $e->event_at?->toISOString(),
            ]),
        ];
    }
}
