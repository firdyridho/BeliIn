<?php

namespace App\Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Order\Http\Requests\AddToCartRequest;
use App\Modules\Order\Models\Cart;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $cart = Cart::with(['items.variant.product.store', 'items.variant.product.images', 'items.variant.inventory'])->firstOrCreate(['user_id' => $request->user()->id]);

        $items = $cart->items->map(function ($item) {
            $variant = $item->variant;
            $product = $variant?->product;

            return [
                'id' => $item->id,
                'variant_id' => $item->product_variant_id,
                'product_variant_id' => $item->product_variant_id,
                'quantity' => $item->quantity,
                'product_name' => $product?->name,
                'variant_name' => $variant?->variant_name,
                'sku' => $variant?->sku,
                'price_amount' => (int) $variant?->price_amount,
                'price' => (int) $variant?->price_amount,
                'line_total' => (int) ($variant?->price_amount * $item->quantity),
                'store_id' => $product?->store_id,
                'store_name' => $product?->store?->name,
                'product_slug' => $product?->slug,
                'image_url' => $product?->images?->first()?->file_path,
                'product' => [
                    'id' => $product?->id,
                    'name' => $product?->name,
                    'slug' => $product?->slug,
                    'store' => $product?->store ? [
                        'id' => $product->store->id,
                        'name' => $product->store->name,
                        'city' => $product->store->city,
                    ] : null,
                    'images' => [
                        ['file_path' => $product?->images?->first()?->file_path, 'image_url' => $product?->images?->first()?->file_path],
                    ],
                ],
                'variant' => [
                    'id' => $variant?->id,
                    'name' => $variant?->variant_name,
                    'price' => (int) $variant?->price_amount,
                    'stock' => $variant?->inventory?->available_quantity ?? 20,
                    'weight_grams' => $product?->weight_gram ?? 250,
                ],
            ];
        });

        return $this->successResponse([
            'id' => $cart->id,
            'items' => $items,
            'total_items' => $items->sum('quantity'),
            'total_price' => $items->sum('line_total'),
        ], 'Cart retrieved successfully');
    }

    public function addItem(AddToCartRequest $request): JsonResponse
    {
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        $variant = ProductVariant::findOrFail($request->input('variant_id'));

        $item = $cart->items()->where('product_variant_id', $variant->id)->first();
        if ($item) {
            $item->quantity += $request->input('quantity');
            $item->save();
        } else {
            $item = $cart->items()->create([
                'product_variant_id' => $variant->id,
                'quantity' => $request->input('quantity'),
            ]);
        }

        return $this->successResponse($item, 'Item added to cart');
    }

    public function removeItem(Request $request, int $itemId): JsonResponse
    {
        $cart = Cart::where('user_id', $request->user()->id)->firstOrFail();
        $cart->items()->where('id', $itemId)->delete();

        return $this->successResponse(null, 'Item removed from cart');
    }
}
