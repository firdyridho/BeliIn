<?php

namespace App\Modules\Order\Services;

use App\Models\User;
use App\Modules\Auth\Models\UserAddress;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Order\Models\Cart;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Models\SellerOrder;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    public function checkout(User $buyer, array $data): Order
    {
        return DB::transaction(function () use ($buyer, $data) {
            $address = UserAddress::where('user_id', $buyer->id)->findOrFail($data['address_id']);
            $cart = Cart::with(['items.variant.product.store'])->firstOrCreate(['user_id' => $buyer->id]);

            $cartItems = $cart->items;
            if (! empty($data['cart_item_ids'])) {
                $cartItems = $cartItems->whereIn('id', $data['cart_item_ids']);
            }

            if ($cartItems->isEmpty()) {
                throw new Exception('Cart is empty', 400);
            }

            // Map store shipping configs by store_id
            $shippingByStore = collect($data['store_shipping'])->keyBy('store_id');

            // Generate unique order number
            $orderNumber = 'ORD-'.date('Ymd').'-'.strtoupper(Str::random(6));

            // Reserve stock for all items
            foreach ($cartItems as $item) {
                $this->inventoryService->reserve(
                    variantId: $item->product_variant_id,
                    quantity: $item->quantity,
                    checkoutRef: $orderNumber,
                    durationMinutes: 15
                );
            }

            // Calculate overall totals
            $overallSubtotal = 0;
            $overallShipping = 0;
            $storeGroups = $cartItems->groupBy(fn ($item) => $item->variant->product->store_id);

            foreach ($storeGroups as $storeId => $items) {
                $storeSubtotal = $items->sum(fn ($i) => $i->variant->price_amount * $i->quantity);
                $overallSubtotal += $storeSubtotal;
                $storeShipping = $shippingByStore->get($storeId)['shipping_amount'] ?? 0;
                $overallShipping += $storeShipping;
            }

            $serviceFee = 1000; // Flat platform service fee in IDR
            $totalAmount = $overallSubtotal + $overallShipping + $serviceFee;

            // Create Parent Order
            $order = Order::create([
                'order_number' => $orderNumber,
                'buyer_id' => $buyer->id,
                'shipping_address_snapshot_json' => [
                    'recipient_name' => $address->recipient_name,
                    'phone' => $address->phone,
                    'address_line' => $address->address_line,
                    'province' => $address->province,
                    'city' => $address->city,
                    'district' => $address->district,
                    'village' => $address->village,
                    'postal_code' => $address->postal_code,
                ],
                'subtotal_amount' => $overallSubtotal,
                'discount_amount' => 0,
                'shipping_amount' => $overallShipping,
                'service_fee_amount' => $serviceFee,
                'total_amount' => $totalAmount,
                'currency' => 'IDR',
                'order_status' => 'pending_payment',
                'placed_at' => now(),
            ]);

            // Create Child Seller Orders and Order Items
            foreach ($storeGroups as $storeId => $items) {
                $storeSubtotal = $items->sum(fn ($i) => $i->variant->price_amount * $i->quantity);
                $storeShipping = $shippingByStore->get($storeId)['shipping_amount'] ?? 0;
                $sellerOrderNumber = 'SO-'.date('Ymd').'-'.strtoupper(Str::random(6));

                $sellerOrder = SellerOrder::create([
                    'order_id' => $order->id,
                    'store_id' => $storeId,
                    'seller_order_number' => $sellerOrderNumber,
                    'subtotal_amount' => $storeSubtotal,
                    'discount_amount' => 0,
                    'shipping_amount' => $storeShipping,
                    'seller_status' => 'pending',
                ]);

                foreach ($items as $item) {
                    $variant = $item->variant;
                    $product = $variant->product;
                    $lineTotal = $variant->price_amount * $item->quantity;

                    OrderItem::create([
                        'seller_order_id' => $sellerOrder->id,
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'product_name_snapshot' => $product->name,
                        'variant_name_snapshot' => $variant->variant_name,
                        'sku_snapshot' => $variant->sku,
                        'unit_price_amount' => $variant->price_amount,
                        'quantity' => $item->quantity,
                        'discount_amount' => 0,
                        'line_total_amount' => $lineTotal,
                        'weight_snapshot_gram' => $product->weight_gram,
                    ]);
                }
            }

            // Remove purchased items from cart
            $cart->items()->whereIn('id', $cartItems->pluck('id'))->delete();

            return $order->load(['sellerOrders.items', 'sellerOrders.store']);
        });
    }
}
