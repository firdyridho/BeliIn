<?php

namespace App\Modules\Shipping\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Models\SellerOrder;
use App\Modules\Shipping\Http\Resources\ShipmentResource;
use App\Modules\Shipping\Models\Shipment;
use App\Modules\Shipping\Services\ShippingManager;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ShippingManager $shippingManager
    ) {}

    public function createForSellerOrder(Request $request, int $sellerOrderId): JsonResponse
    {
        $seller = $request->user()->seller;
        $sellerOrder = SellerOrder::whereHas('store', fn ($q) => $q->where('seller_id', $seller->id))
            ->findOrFail($sellerOrderId);

        $request->validate([
            'shipping_service_id' => ['required', 'exists:shipping_services,id'],
        ]);

        $shipment = $this->shippingManager->createShipmentForOrder(
            sellerOrder: $sellerOrder,
            shippingServiceId: (int) $request->input('shipping_service_id')
        );

        return $this->successResponse([
            'shipment' => new ShipmentResource($shipment->load(['provider', 'service', 'events'])),
        ], 'Shipment created successfully', 201);
    }

    public function track(string $trackingNumber): JsonResponse
    {
        $shipment = Shipment::with(['provider', 'service', 'events'])
            ->where('tracking_number', $trackingNumber)
            ->firstOrFail();

        return $this->successResponse(new ShipmentResource($shipment), 'Shipment tracking details retrieved');
    }

    public function addEvent(Request $request, string $trackingNumber): JsonResponse
    {
        $eventCode = $request->input('event_code') ?: $request->input('status') ?: 'in_transit';
        $desc = $request->input('event_description') ?: $request->input('description') ?: 'Paket sedang dalam perjalanan kurir';
        $location = $request->input('location') ?: 'Hub Transit AnterIn';

        $shipment = $this->shippingManager->addTrackingEvent(
            trackingNumber: $trackingNumber,
            eventCode: $eventCode,
            description: $desc,
            location: $location
        );

        return $this->successResponse(new ShipmentResource($shipment), 'Tracking event recorded');
    }
}
