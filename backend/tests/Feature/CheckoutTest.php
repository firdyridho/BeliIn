<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Auth\Models\UserAddress;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\Inventory;
use App\Modules\Seller\Models\Seller;
use App\Modules\Seller\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function createStoreWithProduct(string $storeName, string $sku, int $price, int $stock = 10): array
    {
        $category = Category::firstOrCreate(
            ['slug' => 'umum'],
            ['name' => 'Kategori Umum', 'status' => 'active']
        );

        $sellerUser = User::factory()->create();
        $seller = Seller::create(['user_id' => $sellerUser->id, 'status' => 'verified']);
        $store = Store::create([
            'seller_id' => $seller->id,
            'name' => $storeName,
            'slug' => str($storeName)->slug(),
            'status' => 'active',
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Produk '.$storeName,
            'slug' => str('Produk '.$storeName)->slug().'-'.uniqid(),
            'weight_gram' => 500,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => $sku,
            'variant_name' => 'Default',
            'price_amount' => $price,
            'status' => 'active',
        ]);

        Inventory::create([
            'product_variant_id' => $variant->id,
            'available_quantity' => $stock,
            'reserved_quantity' => 0,
            'sold_quantity' => 0,
        ]);

        return [$store, $product, $variant];
    }

    public function test_user_can_add_items_to_cart(): void
    {
        $buyer = User::factory()->create();
        [, , $variant] = $this->createStoreWithProduct('Toko A', 'SKU-A1', 50000, 10);

        $response = $this->actingAs($buyer, 'sanctum')->postJson('/api/v1/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Item added to cart',
            ]);

        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
    }

    public function test_multi_seller_checkout_creates_one_parent_order_and_multiple_seller_orders(): void
    {
        $buyer = User::factory()->create();
        $address = UserAddress::create([
            'user_id' => $buyer->id,
            'recipient_name' => 'Budi Pembeli',
            'phone' => '081234567890',
            'address_line' => 'Jl. Tebet Raya No. 10',
            'province' => 'DKI Jakarta',
            'city' => 'Jakarta Selatan',
            'district' => 'Tebet',
            'postal_code' => '12810',
            'is_default' => true,
        ]);

        [$storeA, , $variantA] = $this->createStoreWithProduct('Toko Elektronik', 'VAR-EL-01', 100000, 5);
        [$storeB, , $variantB] = $this->createStoreWithProduct('Toko Fashion', 'VAR-FASH-01', 50000, 5);

        // Add both to cart
        $this->actingAs($buyer, 'sanctum')->postJson('/api/v1/cart/items', [
            'variant_id' => $variantA->id,
            'quantity' => 1,
        ]);
        $this->actingAs($buyer, 'sanctum')->postJson('/api/v1/cart/items', [
            'variant_id' => $variantB->id,
            'quantity' => 2,
        ]);

        // Execute checkout
        $checkoutPayload = [
            'address_id' => $address->id,
            'store_shipping' => [
                [
                    'store_id' => $storeA->id,
                    'shipping_service_code' => 'anterin-reg',
                    'shipping_amount' => 15000,
                ],
                [
                    'store_id' => $storeB->id,
                    'shipping_service_code' => 'anterin-reg',
                    'shipping_amount' => 12000,
                ],
            ],
        ];

        $response = $this->actingAs($buyer, 'sanctum')->postJson('/api/v1/checkout', $checkoutPayload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Checkout successful, order placed',
            ])
            ->assertJsonPath('data.order.order_status', 'pending_payment')
            ->assertJsonPath('data.order.subtotal_amount', 200000) // 1*100k + 2*50k = 200k
            ->assertJsonPath('data.order.shipping_amount', 27000)  // 15k + 12k = 27k
            ->assertJsonPath('data.order.service_fee_amount', 1000)
            ->assertJsonPath('data.order.total_amount', 228000)
            ->assertJsonCount(2, 'data.seller_orders');

        // Check database state
        $this->assertDatabaseHas('orders', [
            'buyer_id' => $buyer->id,
            'order_status' => 'pending_payment',
            'total_amount' => 228000,
        ]);

        $this->assertDatabaseHas('seller_orders', [
            'store_id' => $storeA->id,
            'seller_status' => 'pending',
            'subtotal_amount' => 100000,
            'shipping_amount' => 15000,
        ]);

        $this->assertDatabaseHas('seller_orders', [
            'store_id' => $storeB->id,
            'seller_status' => 'pending',
            'subtotal_amount' => 100000,
            'shipping_amount' => 12000,
        ]);

        // Verify stock was reserved
        $this->assertEquals(4, $variantA->fresh()->inventory->available_quantity);
        $this->assertEquals(1, $variantA->fresh()->inventory->reserved_quantity);

        $this->assertEquals(3, $variantB->fresh()->inventory->available_quantity);
        $this->assertEquals(2, $variantB->fresh()->inventory->reserved_quantity);

        // Verify cart is cleared
        $cartCount = $buyer->fresh()->cart?->items()->count() ?? 0;
        $this->assertEquals(0, $cartCount);
    }
}
