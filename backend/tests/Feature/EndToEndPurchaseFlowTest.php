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
use App\Modules\Seller\Models\Seller;
use App\Modules\Seller\Models\Store;
use App\Modules\Shipping\Models\ShippingProvider;
use App\Modules\Shipping\Models\ShippingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndToEndPurchaseFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_e2e_marketplace_purchase_flow(): void
    {
        // 1. Setup Master Logistics Providers
        $anterin = ShippingProvider::create([
            'code' => 'anterin',
            'name' => 'AnterIn Express',
            'provider_type' => 'internal',
            'is_active' => true,
        ]);

        $regService = ShippingService::create([
            'shipping_provider_id' => $anterin->id,
            'service_code' => 'reg',
            'service_name' => 'AnterIn Regular',
            'estimated_min_days' => 1,
            'estimated_max_days' => 2,
            'is_active' => true,
        ]);

        // 2. Setup Category
        $category = Category::create([
            'name' => 'Elektronik & Gadget',
            'slug' => 'elektronik-gadget',
            'status' => 'active',
        ]);

        // 3. Setup Store 1 (Gadget Store)
        $sellerUser1 = User::factory()->create(['email' => 'seller1@beliin.test']);
        $seller1 = Seller::create(['user_id' => $sellerUser1->id, 'status' => 'verified', 'verified_at' => now()]);
        $store1 = Store::create([
            'seller_id' => $seller1->id,
            'name' => 'Gadget Official Store',
            'slug' => 'gadget-official-store',
            'status' => 'active',
        ]);

        $prod1 = Product::create([
            'store_id' => $store1->id,
            'category_id' => $category->id,
            'name' => 'Earphone Wireless Pro',
            'slug' => 'earphone-wireless-pro',
            'weight_gram' => 250,
        ]);
        $var1 = ProductVariant::create([
            'product_id' => $prod1->id,
            'sku' => 'EPHONE-PRO-01',
            'variant_name' => 'Matte Black',
            'price_amount' => 250000,
        ]);
        Inventory::create([
            'product_variant_id' => $var1->id,
            'available_quantity' => 10,
            'reserved_quantity' => 0,
            'sold_quantity' => 0,
        ]);

        // 4. Setup Store 2 (Fashion Store)
        $sellerUser2 = User::factory()->create(['email' => 'seller2@beliin.test']);
        $seller2 = Seller::create(['user_id' => $sellerUser2->id, 'status' => 'verified', 'verified_at' => now()]);
        $store2 = Store::create([
            'seller_id' => $seller2->id,
            'name' => 'Distro Cloth Official',
            'slug' => 'distro-cloth-official',
            'status' => 'active',
        ]);

        $prod2 = Product::create([
            'store_id' => $store2->id,
            'category_id' => $category->id,
            'name' => 'Hoodie Streetwear',
            'slug' => 'hoodie-streetwear',
            'weight_gram' => 600,
        ]);
        $var2 = ProductVariant::create([
            'product_id' => $prod2->id,
            'sku' => 'HD-STREET-XL',
            'variant_name' => 'Olive Green / XL',
            'price_amount' => 175000,
        ]);
        Inventory::create([
            'product_variant_id' => $var2->id,
            'available_quantity' => 5,
            'reserved_quantity' => 0,
            'sold_quantity' => 0,
        ]);

        // 5. Buyer Registration & Setup
        $regResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'Agus Pembeli',
            'email' => 'agus@example.com',
            'phone' => '081299998888',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $regResponse->assertStatus(201);
        $buyerToken = $regResponse->json('data.token');
        $buyerId = $regResponse->json('data.user.id');

        // 6. Buyer adds shipping address
        $addrResponse = $this->withHeader('Authorization', "Bearer {$buyerToken}")
            ->postJson('/api/v1/addresses', [
                'recipient_name' => 'Agus',
                'phone' => '081299998888',
                'address_line' => 'Jl. Kebon Jeruk No. 88',
                'province' => 'DKI Jakarta',
                'city' => 'Jakarta Barat',
                'district' => 'Kebon Jeruk',
                'postal_code' => '11530',
                'is_default' => true,
            ]);
        $addrResponse->assertStatus(201);
        $addressId = $addrResponse->json('data.id');

        // 7. Buyer adds products from Store 1 and Store 2 to Cart
        $this->withHeader('Authorization', "Bearer {$buyerToken}")
            ->postJson('/api/v1/cart/items', [
                'variant_id' => $var1->id,
                'quantity' => 1,
            ])->assertStatus(200);

        $this->withHeader('Authorization', "Bearer {$buyerToken}")
            ->postJson('/api/v1/cart/items', [
                'variant_id' => $var2->id,
                'quantity' => 2,
            ])->assertStatus(200);

        // 8. Multi-Seller Checkout
        // Expected subtotal = 1*250k + 2*175k = 600,000 IDR
        // Shipping = 10,000 + 10,000 = 20,000 IDR
        // Service fee = 1,000 IDR
        // Total = 621,000 IDR
        $checkoutResponse = $this->withHeader('Authorization', "Bearer {$buyerToken}")
            ->postJson('/api/v1/checkout', [
                'address_id' => $addressId,
                'store_shipping' => [
                    [
                        'store_id' => $store1->id,
                        'shipping_service_code' => 'reg',
                        'shipping_amount' => 10000,
                    ],
                    [
                        'store_id' => $store2->id,
                        'shipping_service_code' => 'reg',
                        'shipping_amount' => 10000,
                    ],
                ],
            ]);

        $checkoutResponse->assertStatus(201)
            ->assertJsonPath('data.order.order_status', 'pending_payment')
            ->assertJsonPath('data.order.subtotal_amount', 600000)
            ->assertJsonPath('data.order.shipping_amount', 20000)
            ->assertJsonPath('data.order.service_fee_amount', 1000)
            ->assertJsonPath('data.order.total_amount', 621000)
            ->assertJsonCount(2, 'data.seller_orders');

        $orderId = $checkoutResponse->json('data.order.id');
        $sellerOrder1Id = $checkoutResponse->json('data.seller_orders.0.id');
        $sellerOrder2Id = $checkoutResponse->json('data.seller_orders.1.id');

        // 9. Initiate Payment
        $payInitResponse = $this->withHeader('Authorization', "Bearer {$buyerToken}")
            ->postJson("/api/v1/payments/{$orderId}/initiate", ['payment_method' => 'qris']);
        $payInitResponse->assertStatus(200);
        $providerPaymentId = $payInitResponse->json('data.provider_payment_id');

        // 10. Webhook arrives from Payment Gateway: SUCCESS
        $webhookSig = hash_hmac('sha256', "{$providerPaymentId}:paid:621000", config('app.key'));
        $webhookResponse = $this->postJson('/api/v1/webhooks/payment/simulated', [
            'provider_payment_id' => $providerPaymentId,
            'provider_transaction_id' => 'TXN-E2E-'.uniqid(),
            'status' => 'paid',
            'amount' => 621000,
            'signature' => $webhookSig,
        ]);
        $webhookResponse->assertStatus(200);

        // Verify Order is processing and both Seller Orders are accepted
        $parentOrder = Order::with('sellerOrders')->find($orderId);
        $this->assertEquals('processing', $parentOrder->order_status);
        $this->assertEquals('accepted', $parentOrder->sellerOrders[0]->seller_status);
        $this->assertEquals('accepted', $parentOrder->sellerOrders[1]->seller_status);

        // Verify stock is committed to sold
        $this->assertEquals(9, $var1->fresh()->inventory->available_quantity);
        $this->assertEquals(1, $var1->fresh()->inventory->sold_quantity);
        $this->assertEquals(3, $var2->fresh()->inventory->available_quantity);
        $this->assertEquals(2, $var2->fresh()->inventory->sold_quantity);

        // 11. Store 1 ships seller order via AnterIn
        $shipResponse1 = $this->actingAs($sellerUser1, 'sanctum')
            ->postJson("/api/v1/seller/orders/{$sellerOrder1Id}/ship", [
                'shipping_service_id' => $regService->id,
            ]);
        $shipResponse1->assertStatus(201);
        $trackingNumber1 = $shipResponse1->json('data.shipment.tracking_number');

        // 12. Store 2 ships seller order via AnterIn
        $shipResponse2 = $this->actingAs($sellerUser2, 'sanctum')
            ->postJson("/api/v1/seller/orders/{$sellerOrder2Id}/ship", [
                'shipping_service_id' => $regService->id,
            ]);
        $shipResponse2->assertStatus(201);
        $trackingNumber2 = $shipResponse2->json('data.shipment.tracking_number');

        // 13. Courier delivers Store 1 package
        $this->postJson("/api/v1/shipments/{$trackingNumber1}/events", [
            'event_code' => 'DELIVERED',
            'event_description' => 'Paket diterima oleh Agus (Penerima)',
            'location' => 'Jakarta Barat',
        ])->assertStatus(200);

        // Parent order should still be 'processing' because Store 2 is not yet delivered
        $this->assertEquals('processing', $parentOrder->fresh()->order_status);

        // 14. Courier delivers Store 2 package
        $this->postJson("/api/v1/shipments/{$trackingNumber2}/events", [
            'event_code' => 'DELIVERED',
            'event_description' => 'Paket diterima oleh Agus (Penerima)',
            'location' => 'Jakarta Barat',
        ])->assertStatus(200);

        // Now parent order MUST be 'completed'!
        $this->assertEquals('completed', $parentOrder->fresh()->order_status);

        // 15. Check public tracking history
        $trackCheck = $this->getJson("/api/v1/shipments/track/{$trackingNumber1}");
        $trackCheck->assertStatus(200)
            ->assertJsonPath('data.status', 'delivered')
            ->assertJsonCount(2, 'data.events');
    }
}
