<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Seller\Models\Seller;
use App\Modules\Seller\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_user_can_list_categories_and_products(): void
    {
        $category = Category::create([
            'name' => 'Elektronik',
            'slug' => 'elektronik',
            'status' => 'active',
        ]);

        $sellerUser = User::factory()->create();
        $seller = Seller::create([
            'user_id' => $sellerUser->id,
            'status' => 'verified',
            'verified_at' => now(),
        ]);
        $store = Store::create([
            'seller_id' => $seller->id,
            'name' => 'Toko Gadget',
            'slug' => 'toko-gadget',
            'status' => 'active',
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Smartphone Pro 128GB',
            'slug' => 'smartphone-pro-128gb',
            'description' => 'Smartphone canggih',
            'weight_gram' => 300,
            'status' => 'published',
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SP-PRO-128-BLK',
            'variant_name' => 'Hitam 128GB',
            'price_amount' => 4500000,
            'status' => 'active',
        ]);

        // Categories check
        $catResponse = $this->getJson('/api/v1/categories');
        $catResponse->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Elektronik');

        // Products check
        $prodResponse = $this->getJson('/api/v1/products?q=Smartphone');
        $prodResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Smartphone Pro 128GB')
            ->assertJsonPath('data.0.min_price', 4500000);
    }

    public function test_public_user_can_view_product_detail_by_slug(): void
    {
        $category = Category::create(['name' => 'Fashion', 'slug' => 'fashion']);
        $sellerUser = User::factory()->create();
        $seller = Seller::create(['user_id' => $sellerUser->id, 'status' => 'verified']);
        $store = Store::create(['seller_id' => $seller->id, 'name' => 'Baju Keren', 'slug' => 'baju-keren']);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Kaos Polos Premium',
            'slug' => 'kaos-polos-premium',
            'description' => 'Bahan katun combed 30s',
            'weight_gram' => 200,
            'status' => 'published',
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'KAOS-WHT-L',
            'variant_name' => 'Putih / L',
            'price_amount' => 75000,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/products/kaos-polos-premium');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Kaos Polos Premium',
                    'slug' => 'kaos-polos-premium',
                ],
            ])
            ->assertJsonCount(1, 'data.variants');
    }

    public function test_verified_seller_can_create_product_with_variants(): void
    {
        $sellerUser = User::factory()->create();
        $seller = Seller::create(['user_id' => $sellerUser->id, 'status' => 'verified']);
        Store::create(['seller_id' => $seller->id, 'name' => 'Toko Buku', 'slug' => 'toko-buku', 'status' => 'active']);
        $category = Category::create(['name' => 'Buku', 'slug' => 'buku']);

        $payload = [
            'category_id' => $category->id,
            'name' => 'Buku Pemrograman Laravel',
            'description' => 'Panduan lengkap membuat REST API',
            'weight_gram' => 400,
            'variants' => [
                [
                    'sku' => 'BK-LRV-001',
                    'variant_name' => 'Edisi Standar',
                    'price_amount' => 150000,
                ],
                [
                    'sku' => 'BK-LRV-002',
                    'variant_name' => 'Edisi Hardcover',
                    'price_amount' => 250000,
                ],
            ],
        ];

        $response = $this->actingAs($sellerUser, 'sanctum')->postJson('/api/v1/seller/products', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Product created successfully',
            ])
            ->assertJsonCount(2, 'data.variants');

        $this->assertDatabaseHas('products', [
            'name' => 'Buku Pemrograman Laravel',
        ]);

        $this->assertDatabaseHas('product_variants', [
            'sku' => 'BK-LRV-001',
            'price_amount' => 150000,
        ]);
    }
}
