<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\Inventory;
use App\Modules\Inventory\Models\InventoryReservation;
use App\Modules\Seller\Models\Seller;
use App\Modules\Seller\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleaseExpiredReservationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_artisan_command_releases_expired_reservations(): void
    {
        $category = Category::create(['name' => 'Cat Expire', 'slug' => 'cat-expire']);
        $sellerUser = User::factory()->create();
        $seller = Seller::create(['user_id' => $sellerUser->id, 'status' => 'verified']);
        $store = Store::create(['seller_id' => $seller->id, 'name' => 'Store Expire', 'slug' => 'store-expire']);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Product Expire Test',
            'slug' => 'product-expire-test',
            'weight_gram' => 200,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-EXPIRE-1',
            'variant_name' => 'Default',
            'price_amount' => 50000,
        ]);

        $inventory = Inventory::create([
            'product_variant_id' => $variant->id,
            'available_quantity' => 10,
            'reserved_quantity' => 0,
            'sold_quantity' => 0,
        ]);

        // Manually create an expired reservation (expired 5 minutes ago)
        $inventory->update([
            'available_quantity' => 8,
            'reserved_quantity' => 2,
        ]);

        $reservation = InventoryReservation::create([
            'inventory_id' => $inventory->id,
            'checkout_reference' => 'CHK-EXPIRED-TEST',
            'quantity' => 2,
            'status' => 'active',
            'expires_at' => now()->subMinutes(5),
        ]);

        $this->artisan('inventory:release-expired')
            ->expectsOutput('Scanning for expired inventory reservations...')
            ->expectsOutput('Successfully released 1 expired reservation(s).')
            ->assertSuccessful();

        $inventory->refresh();
        $this->assertEquals(10, $inventory->available_quantity);
        $this->assertEquals(0, $inventory->reserved_quantity);
        $this->assertEquals('expired', $reservation->fresh()->status);
    }
}
