<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Http\Requests\StockAdjustmentRequest;
use App\Modules\Inventory\Http\Resources\InventoryResource;
use App\Modules\Inventory\Models\Inventory;
use App\Modules\Inventory\Services\InventoryService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerInventoryController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $seller = $request->user()->seller;
        if (! $seller || ! $seller->store) {
            return $this->errorResponse('Store not found', 404);
        }

        $inventories = Inventory::whereHas('variant.product', function ($q) use ($seller) {
            $q->where('store_id', $seller->store->id);
        })->with('variant.product')->paginate(20);

        return $this->successResponse(
            InventoryResource::collection($inventories),
            'Inventory list retrieved',
            200,
            [
                'current_page' => $inventories->currentPage(),
                'total' => $inventories->total(),
                'per_page' => $inventories->perPage(),
            ]
        );
    }

    public function adjust(StockAdjustmentRequest $request): JsonResponse
    {
        $seller = $request->user()->seller;
        $variant = ProductVariant::whereHas('product', function ($q) use ($seller) {
            $q->where('store_id', $seller->store->id);
        })->findOrFail($request->input('variant_id'));

        $inventory = $this->inventoryService->adjustStock(
            variantId: $variant->id,
            quantityDelta: (int) $request->input('quantity_delta'),
            reason: $request->input('reason'),
            actorId: $request->user()->id
        );

        return $this->successResponse(
            new InventoryResource($inventory->load('variant')),
            'Stock adjusted successfully'
        );
    }
}
