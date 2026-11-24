<?php

namespace App\Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Http\Resources\OrderResource;
use App\Modules\Order\Http\Resources\SellerOrderResource;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\SellerOrder;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ApiResponse;

    /**
     * List buyer orders
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::with([
            'sellerOrders.items',
            'sellerOrders.store',
            'sellerOrders.shipment.provider',
            'sellerOrders.shipment.service',
            'sellerOrders.shipment.trackingEvents',
            'payment',
        ])
            ->where('buyer_id', $request->user()->id)
            ->latest()
            ->paginate($request->input('per_page', 15));

        return $this->successResponse(
            OrderResource::collection($orders),
            'Orders retrieved successfully',
            200,
            [
                'current_page' => $orders->currentPage(),
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'last_page' => $orders->lastPage(),
            ]
        );
    }

    /**
     * Get buyer order detail
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $order = Order::with([
            'sellerOrders.items',
            'sellerOrders.store',
            'sellerOrders.shipment.provider',
            'sellerOrders.shipment.service',
            'sellerOrders.shipment.trackingEvents',
            'payment',
        ])
            ->where('buyer_id', $request->user()->id)
            ->findOrFail($id);

        return $this->successResponse(new OrderResource($order), 'Order detail retrieved successfully');
    }

    /**
     * List seller orders for authenticated seller's store
     */
    public function sellerOrders(Request $request): JsonResponse
    {
        $seller = $request->user()->seller;
        if (! $seller || ! $seller->store) {
            return $this->errorResponse('Toko tidak ditemukan untuk akun ini', 404);
        }

        $sellerOrders = SellerOrder::with([
            'order',
            'items',
            'shipment.provider',
            'shipment.service',
            'shipment.trackingEvents',
        ])
            ->where('store_id', $seller->store->id)
            ->latest()
            ->paginate($request->input('per_page', 20));

        return $this->successResponse(
            SellerOrderResource::collection($sellerOrders),
            'Seller orders retrieved successfully',
            200,
            [
                'current_page' => $sellerOrders->currentPage(),
                'total' => $sellerOrders->total(),
                'per_page' => $sellerOrders->perPage(),
                'last_page' => $sellerOrders->lastPage(),
            ]
        );
    }

    /**
     * Update seller order status
     */
    public function updateSellerOrderStatus(Request $request, int $id): JsonResponse
    {
        $seller = $request->user()->seller;
        if (! $seller || ! $seller->store) {
            return $this->errorResponse('Toko tidak ditemukan untuk akun ini', 404);
        }

        $sellerOrder = SellerOrder::where('store_id', $seller->store->id)->findOrFail($id);

        $request->validate([
            'status' => ['required', 'string', 'in:accepted,processing,completed,cancelled'],
        ]);

        $sellerOrder->update([
            'seller_status' => $request->input('status'),
            'accepted_at' => $request->input('status') === 'accepted' ? now() : $sellerOrder->accepted_at,
            'completed_at' => $request->input('status') === 'completed' ? now() : $sellerOrder->completed_at,
            'cancelled_at' => $request->input('status') === 'cancelled' ? now() : $sellerOrder->cancelled_at,
        ]);

        return $this->successResponse(new SellerOrderResource($sellerOrder), 'Status pesanan berhasil diperbarui');
    }
}
