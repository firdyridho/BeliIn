<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\UserAddress;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\Inventory;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Models\SellerOrder;
use App\Modules\Payment\Models\Payment;
use App\Modules\Seller\Models\Seller;
use App\Modules\Seller\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_list_and_view_their_orders(): void
    {
        $buyer = User::factory()->create();
        $buyerRole = Role::firstOrCreate(['slug' => 'buyer'], ['name' => 'Buyer']);
        $buyer->roles()->attach($buyerRole);

        $sellerUser = User::factory()->create();
        $seller = Seller::create(['user_id' => $sellerUser->id, 'status' => 'verified']);
        $store = Store::create([
            'seller_id' => $seller->id,
            'name' => 'Store Test',
            'slug' => 'store-test',
            'city' => 'Jakarta Selatan',
            'status' => 'active',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-001',
            'buyer_id' => $buyer->id,
            'shipping_address_snapshot_json' => ['recipient_name' => 'Budi'],
            'subtotal_amount' => 100000,
            'discount_amount' => 0,
            'shipping_amount' => 15000,
            'service_fee_amount' => 1000,
            'total_amount' => 116000,
            'currency' => 'IDR',
            'order_status' => 'pending_payment',
        ]);

        $sellerOrder = SellerOrder::create([
            'order_id' => $order->id,
            'store_id' => $store->id,
            'seller_order_number' => 'SO-TEST-001',
            'subtotal_amount' => 100000,
            'discount_amount' => 0,
            'shipping_amount' => 15000,
            'seller_status' => 'pending',
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'simulated',
            'provider_payment_id' => 'PAY-TEST-001',
            'payment_method' => 'qris',
            'amount' => 116000,
            'currency' => 'IDR',
            'status' => 'pending',
            'expires_at' => now()->addMinutes(15),
        ]);

        // 1. List buyer orders
        $resList = $this->actingAs($buyer)->getJson('/api/v1/orders');
        $resList->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.order_number', 'ORD-TEST-001');

        // 2. View order detail
        $resDetail = $this->actingAs($buyer)->getJson("/api/v1/orders/{$order->id}");
        $resDetail->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order_number', 'ORD-TEST-001')
            ->assertJsonPath('data.total_amount', 116000);

        // 3. Seller orders list
        $resSellerList = $this->actingAs($sellerUser)->getJson('/api/v1/seller/orders');
        $resSellerList->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.seller_order_number', 'SO-TEST-001');

        // 4. Update seller order status
        $resUpdate = $this->actingAs($sellerUser)->putJson("/api/v1/seller/orders/{$sellerOrder->id}/status", [
            'status' => 'accepted',
        ]);
        $resUpdate->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'accepted');
    }
}
