<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Models\Inventory;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Seller\Models\Seller;
use App\Modules\Seller\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = app(InventoryService::class);
    }

    private function createVariantWithStock(int $initialStock = 10): ProductVariant
    {
        $category = Category::create(['name' => 'Test Cat', 'slug' => 'test-cat-'.uniqid()]);
        $sellerUser = User::factory()->create();
        $seller = Seller::create(['user_id' => $sellerUser->id, 'status' => 'verified']);
        $store = Store::create(['seller_id' => $seller->id, 'name' => 'Store '.uniqid(), 'slug' => 'store-'.uniqid()]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Product '.uniqid(),
            'slug' => 'product-'.uniqid(),
            'weight_gram' => 200,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-'.uniqid(),
            'variant_name' => 'Varian Test',
            'price_amount' => 100000,
        ]);

        Inventory::create([
            'product_variant_id' => $variant->id,
            'available_quantity' => $initialStock,
            'reserved_quantity' => 0,
            'sold_quantity' => 0,
        ]);

        return $variant;
    }

    public function test_can_reserve_stock_atomically(): void
    {
        $variant = $this->createVariantWithStock(5);

        $reservation = $this->inventoryService->reserve(
            variantId: $variant->id,
            quantity: 2,
            checkoutRef: 'CHK-12345'
        );

        $this->assertEquals('active', $reservation->status);
        $this->assertEquals(2, $reservation->quantity);

        $inventory = $variant->fresh()->inventory;
        $this->assertEquals(3, $inventory->available_quantity);
        $this->assertEquals(2, $inventory->reserved_quantity);

        $this->assertDatabaseHas('inventory_movements', [
            'inventory_id' => $inventory->id,
            'movement_type' => 'reserved',
            'quantity_delta' => -2,
        ]);
    }

    public function test_cannot_oversell_when_stock_is_insufficient(): void
    {
        $variant = $this->createVariantWithStock(1);

        // First reservation consumes the 1 available item
        $this->inventoryService->reserve(
            variantId: $variant->id,
            quantity: 1,
            checkoutRef: 'CHK-FIRST'
        );

        // Second reservation must fail
        $this->expectException(InsufficientStockException::class);
        $this->inventoryService->reserve(
            variantId: $variant->id,
            quantity: 1,
            checkoutRef: 'CHK-SECOND'
        );
    }

    public function test_can_release_reservation_back_to_available_stock(): void
    {
        $variant = $this->createVariantWithStock(5);

        $reservation = $this->inventoryService->reserve(
            variantId: $variant->id,
            quantity: 3,
            checkoutRef: 'CHK-RELEASE'
        );

        $this->assertEquals(2, $variant->fresh()->inventory->available_quantity);

        $this->inventoryService->releaseReservation($reservation);

        $inventory = $variant->fresh()->inventory;
        $this->assertEquals(5, $inventory->available_quantity);
        $this->assertEquals(0, $inventory->reserved_quantity);
        $this->assertEquals('released', $reservation->fresh()->status);
    }

    public function test_can_commit_reservation_to_sold_quantity(): void
    {
        $variant = $this->createVariantWithStock(10);

        $reservation = $this->inventoryService->reserve(
            variantId: $variant->id,
            quantity: 4,
            checkoutRef: 'CHK-COMMIT'
        );

        $this->inventoryService->commitReservation($reservation);

        $inventory = $variant->fresh()->inventory;
        $this->assertEquals(6, $inventory->available_quantity);
        $this->assertEquals(0, $inventory->reserved_quantity);
        $this->assertEquals(4, $inventory->sold_quantity);
        $this->assertEquals('converted', $reservation->fresh()->status);
    }

    public function test_seller_can_adjust_stock_via_api(): void
    {
        $variant = $this->createVariantWithStock(10);
        $sellerUser = $variant->product->store->seller->user;

        $response = $this->actingAs($sellerUser, 'sanctum')->postJson('/api/v1/seller/inventory/adjust', [
            'variant_id' => $variant->id,
            'quantity_delta' => 15,
            'reason' => 'Restock pengadaan barang baru',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Stock adjusted successfully',
            ])
            ->assertJsonPath('data.available_quantity', 25);

        $this->assertEquals(25, $variant->fresh()->inventory->available_quantity);
    }
}
