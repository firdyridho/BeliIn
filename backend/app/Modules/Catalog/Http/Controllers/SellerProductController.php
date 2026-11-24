<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Http\Requests\StoreProductRequest;
use App\Modules\Catalog\Http\Requests\UpdateProductRequest;
use App\Modules\Catalog\Http\Resources\ProductDetailResource;
use App\Modules\Catalog\Http\Resources\ProductResource;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\Inventory;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SellerProductController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $seller = $request->user()->seller;
        if (! $seller || ! $seller->store) {
            return $this->errorResponse('Store not found', 404);
        }

        $products = Product::with(['variants', 'images', 'category'])
            ->where('store_id', $seller->store->id)
            ->latest()
            ->paginate(20);

        return $this->successResponse(
            ProductResource::collection($products),
            'Seller products retrieved',
            200,
            [
                'current_page' => $products->currentPage(),
                'total' => $products->total(),
                'per_page' => $products->perPage(),
            ]
        );
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $seller = $request->user()->seller;
        $store = $seller->store;
        $validated = $request->validated();

        return DB::transaction(function () use ($store, $validated) {
            $product = Product::create([
                'store_id' => $store->id,
                'category_id' => $validated['category_id'],
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']).'-'.Str::random(6),
                'description' => $validated['description'] ?? null,
                'condition' => $validated['condition'] ?? 'new',
                'weight_gram' => $validated['weight_gram'],
                'status' => 'published',
                'published_at' => now(),
            ]);

            foreach ($validated['variants'] as $variantData) {
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $variantData['sku'],
                    'variant_name' => $variantData['variant_name'],
                    'price_amount' => $variantData['price_amount'],
                    'compare_at_price_amount' => $variantData['compare_at_price_amount'] ?? null,
                    'attributes_json' => $variantData['attributes'] ?? null,
                    'status' => 'active',
                ]);

                // Create initial inventory record if inventory table exists
                if (class_exists(Inventory::class) && Schema::hasTable('inventories')) {
                    Inventory::create([
                        'product_variant_id' => $variant->id,
                        'available_quantity' => 0,
                        'reserved_quantity' => 0,
                        'sold_quantity' => 0,
                    ]);
                }
            }

            return $this->successResponse(
                new ProductDetailResource($product->load(['variants', 'images', 'category', 'store'])),
                'Product created successfully',
                201
            );
        });
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $seller = $request->user()->seller;
        $product = Product::with(['variants', 'images', 'category', 'store'])
            ->where('store_id', $seller->store->id)
            ->findOrFail($id);

        return $this->successResponse(new ProductDetailResource($product), 'Product details retrieved');
    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $seller = $request->user()->seller;
        $product = Product::where('store_id', $seller->store->id)->findOrFail($id);

        $product->update($request->validated());

        return $this->successResponse(new ProductDetailResource($product->load(['variants', 'images', 'category', 'store'])), 'Product updated successfully');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $seller = $request->user()->seller;
        $product = Product::where('store_id', $seller->store->id)->findOrFail($id);
        $product->delete();

        return $this->successResponse(null, 'Product deleted successfully');
    }
}
