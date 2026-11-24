<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Models\SellerOrder;
use App\Modules\Seller\Models\Seller;
use App\Modules\Seller\Models\Store;
use App\Modules\Shipping\Models\ShippingProvider;
use App\Modules\Shipping\Models\ShippingService;
use App\Modules\Shipping\Services\ShippingManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingTest extends TestCase
{
    use RefreshDatabase;

    private function createAcceptedOrder(): array
    {
        $buyer = User::factory()->create();
        $sellerUser = User::factory()->create();
        $seller = Seller::create(['user_id' => $sellerUser->id, 'status' => 'verified']);
        $store = Store::create(['seller_id' => $seller->id, 'name' => 'Store Kirim', 'slug' => 'store-kirim']);
        $category = Category::create(['name' => 'Kategori Kirim', 'slug' => 'kategori-kirim']);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Produk Kirim',
            'slug' => 'produk-kirim-'.uniqid(),
            'weight_gram' => 1200,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-KIRIM-1',
            'variant_name' => 'Default',
            'price_amount' => 150000,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-SHIP-'.uniqid(),
            'buyer_id' => $buyer->id,
            'shipping_address_snapshot_json' => [
                'recipient_name' => 'Budi Penerima',
                'phone' => '081234567890',
                'address_line' => 'Jl. Pengiriman No. 12',
                'city' => 'Jakarta Selatan',
                'province' => 'DKI Jakarta',
                'postal_code' => '12160',
            ],
            'subtotal_amount' => 150000,
            'shipping_amount' => 18000,
            'service_fee_amount' => 1000,
            'total_amount' => 169000,
            'order_status' => 'processing',
        ]);

        $sellerOrder = SellerOrder::create([
            'order_id' => $order->id,
            'store_id' => $store->id,
            'seller_order_number' => 'SO-SHIP-'.uniqid(),
            'subtotal_amount' => 150000,
            'shipping_amount' => 18000,
            'seller_status' => 'accepted',
            'accepted_at' => now(),
        ]);

        OrderItem::create([
            'seller_order_id' => $sellerOrder->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name_snapshot' => $product->name,
            'variant_name_snapshot' => $variant->variant_name,
            'sku_snapshot' => $variant->sku,
            'unit_price_amount' => 150000,
            'quantity' => 1,
            'line_total_amount' => 150000,
            'weight_snapshot_gram' => 1200,
        ]);

        $provider = ShippingProvider::create([
            'code' => 'anterin',
            'name' => 'AnterIn Express',
            'provider_type' => 'internal',
            'is_active' => true,
        ]);

        $service = ShippingService::create([
            'shipping_provider_id' => $provider->id,
            'service_code' => 'reg',
            'service_name' => 'AnterIn Regular',
            'estimated_min_days' => 1,
            'estimated_max_days' => 2,
            'is_active' => true,
        ]);

        return [$order, $sellerOrder, $sellerUser, $service];
    }

    public function test_can_calculate_shipping_rates_for_anterin(): void
    {
        $this->createAcceptedOrder();

        $response = $this->getJson('/api/v1/shipping/rates?origin_city=Jakarta+Selatan&destination_city=Bandung&weight_gram=1200');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'provider_code',
                        'provider_name',
                        'service_code',
                        'service_name',
                        'cost_amount',
                        'estimated_days',
                    ],
                ],
            ]);
    }

    public function test_seller_can_create_shipment_for_accepted_order(): void
    {
        [$order, $sellerOrder, $sellerUser, $service] = $this->createAcceptedOrder();

        $response = $this->actingAs($sellerUser, 'sanctum')
            ->postJson("/api/v1/seller/orders/{$sellerOrder->id}/ship", [
                'shipping_service_id' => $service->id,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Shipment created successfully',
            ])
            ->assertJsonPath('data.shipment.status', 'pending_pickup')
            ->assertJsonStructure([
                'data' => [
                    'shipment' => [
                        'tracking_number',
                        'status',
                    ],
                ],
            ]);

        // Seller order status advances to ready_to_ship
        $this->assertEquals('ready_to_ship', $sellerOrder->fresh()->seller_status);

        $this->assertDatabaseHas('shipments', [
            'seller_order_id' => $sellerOrder->id,
            'status' => 'pending_pickup',
        ]);
    }

    public function test_can_track_shipment_events_and_advance_delivery_status(): void
    {
        [$order, $sellerOrder, $sellerUser, $service] = $this->createAcceptedOrder();

        // Create shipment
        $shipResponse = $this->actingAs($sellerUser, 'sanctum')
            ->postJson("/api/v1/seller/orders/{$sellerOrder->id}/ship", [
                'shipping_service_id' => $service->id,
            ]);

        $trackingNumber = $shipResponse->json('data.shipment.tracking_number');

        // Add tracking event (DELIVERED)
        $eventResponse = $this->postJson("/api/v1/shipments/{$trackingNumber}/events", [
            'event_code' => 'DELIVERED',
            'event_description' => 'Paket telah diterima oleh penerima',
            'location' => 'Jakarta Selatan',
        ]);

        $eventResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tracking event recorded',
            ]);

        // Verify shipment status is delivered
        $this->assertDatabaseHas('shipments', [
            'tracking_number' => $trackingNumber,
            'status' => 'delivered',
        ]);

        // Verify seller order status is delivered
        $this->assertEquals('delivered', $sellerOrder->fresh()->seller_status);

        // Verify parent order status is completed
        $this->assertEquals('completed', $order->fresh()->order_status);

        // Check public tracking query
        $trackResponse = $this->getJson("/api/v1/shipments/track/{$trackingNumber}");
        $trackResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'delivered')
            ->assertJsonCount(2, 'data.events'); // 1 initial creation + 1 delivered
    }
}
