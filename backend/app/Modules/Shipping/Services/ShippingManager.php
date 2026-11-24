<?php

namespace App\Modules\Shipping\Services;

use App\Modules\Order\Models\SellerOrder;
use App\Modules\Shipping\Contracts\ShippingProviderInterface;
use App\Modules\Shipping\Models\Shipment;
use App\Modules\Shipping\Models\ShipmentTrackingEvent;
use App\Modules\Shipping\Models\ShippingProvider;
use App\Modules\Shipping\Models\ShippingService;
use Exception;
use Illuminate\Support\Facades\DB;

class ShippingManager
{
    public function getProvider(string $code): ShippingProviderInterface
    {
        return match ($code) {
            'anterin' => app(AnterInShippingProvider::class),
            default => app(AnterInShippingProvider::class),
        };
    }

    public function calculateAllRates(string $originCity, string $destinationCity, int $weightGram): array
    {
        $services = ShippingService::with('provider')->where('is_active', true)->get();
        $rates = [];

        foreach ($services as $service) {
            $provider = $this->getProvider($service->provider->code);
            $rates[] = $provider->calculateRate($originCity, $destinationCity, $weightGram, $service);
        }

        return $rates;
    }

    public function createShipmentForOrder(SellerOrder $sellerOrder, int $shippingServiceId): Shipment
    {
        return DB::transaction(function () use ($sellerOrder, $shippingServiceId) {
            if ($sellerOrder->shipment) {
                return $sellerOrder->shipment;
            }

            $service = ShippingService::with('provider')->findOrFail($shippingServiceId);
            $provider = $this->getProvider($service->provider->code);
            $shipmentData = $provider->createShipment($sellerOrder, $service);

            $parentOrder = $sellerOrder->order;
            $destination = $parentOrder->shipping_address_snapshot_json ?? [];
            $origin = [
                'store_name' => $sellerOrder->store->name,
                'city' => 'Jakarta Selatan', // Store operational default
            ];

            $totalWeight = $sellerOrder->items->sum(fn ($item) => ($item->weight_snapshot_gram ?? 100) * $item->quantity);

            $shipment = Shipment::create([
                'seller_order_id' => $sellerOrder->id,
                'shipping_provider_id' => $service->shipping_provider_id,
                'shipping_service_id' => $service->id,
                'tracking_number' => $shipmentData['tracking_number'],
                'status' => 'pending_pickup',
                'origin_snapshot_json' => $origin,
                'destination_snapshot_json' => $destination,
                'shipping_cost_amount' => $sellerOrder->shipping_amount,
                'buyer_shipping_amount' => $sellerOrder->shipping_amount,
                'provider_cost_amount' => $shipmentData['provider_cost'] ?? 0,
                'weight_gram' => $totalWeight,
            ]);

            // Create initial tracking event
            ShipmentTrackingEvent::create([
                'shipment_id' => $shipment->id,
                'event_code' => 'CREATED',
                'event_description' => "Pengiriman dibuat dengan nomor resi {$shipment->tracking_number}. Menunggu penjemputan oleh kurir.",
                'location' => $origin['city'],
                'event_at' => now(),
                'source' => 'system',
            ]);

            // Advance seller order status: accepted -> ready_to_ship
            $sellerOrder->update(['seller_status' => 'ready_to_ship']);

            return $shipment;
        });
    }

    public function addTrackingEvent(string $trackingNumber, string $eventCode, string $description, ?string $location = null): Shipment
    {
        return DB::transaction(function () use ($trackingNumber, $eventCode, $description, $location) {
            $shipment = Shipment::with(['sellerOrder.order'])->where('tracking_number', $trackingNumber)->firstOrFail();

            ShipmentTrackingEvent::create([
                'shipment_id' => $shipment->id,
                'event_code' => $eventCode,
                'event_description' => $description,
                'location' => $location,
                'event_at' => now(),
                'source' => 'courier',
            ]);

            $statusMapping = [
                'PICKED_UP' => 'picked_up',
                'IN_TRANSIT' => 'in_transit',
                'OUT_FOR_DELIVERY' => 'out_for_delivery',
                'DELIVERED' => 'delivered',
                'FAILED' => 'failed',
                'RETURNED' => 'returned',
            ];

            if (isset($statusMapping[$eventCode])) {
                $newShipmentStatus = $statusMapping[$eventCode];
                $updateData = ['status' => $newShipmentStatus];

                if ($newShipmentStatus === 'picked_up') {
                    $updateData['shipped_at'] = now();
                    $shipment->sellerOrder->update(['seller_status' => 'shipped', 'shipped_at' => now()]);
                } elseif ($newShipmentStatus === 'delivered') {
                    $updateData['delivered_at'] = now();
                    $shipment->sellerOrder->update(['seller_status' => 'delivered', 'completed_at' => now()]);

                    // Check parent order completion: if all seller orders are delivered, complete the parent order
                    $parentOrder = $shipment->sellerOrder->order;
                    $pendingSellerOrders = $parentOrder->sellerOrders()->where('seller_status', '!=', 'delivered')->count();
                    if ($pendingSellerOrders === 0) {
                        $parentOrder->update(['order_status' => 'completed']);
                    }
                }

                $shipment->update($updateData);
            }

            return $shipment->fresh(['events', 'provider', 'service', 'sellerOrder']);
        });
    }
}
