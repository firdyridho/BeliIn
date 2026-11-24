<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Auth\Models\Role;
use App\Modules\Seller\Models\Seller;
use App\Modules\Seller\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_seller_application_and_create_store(): void
    {
        $user = User::factory()->create();

        $payload = [
            'store_name' => 'Toko Berkah Elektronik',
            'store_description' => 'Menjual barang elektronik original bergaransi',
            'document_type' => 'ktp',
            'document_number' => '3201234567890001',
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/seller/applications', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Seller application submitted successfully',
            ])
            ->assertJsonPath('data.seller.status', 'pending')
            ->assertJsonPath('data.store.name', 'Toko Berkah Elektronik');

        $this->assertDatabaseHas('sellers', [
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('stores', [
            'name' => 'Toko Berkah Elektronik',
            'slug' => 'toko-berkah-elektronik',
            'status' => 'pending',
        ]);
    }

    public function test_user_cannot_submit_duplicate_seller_application(): void
    {
        $user = User::factory()->create();

        $seller = Seller::create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        Store::create([
            'seller_id' => $seller->id,
            'name' => 'Existing Store',
            'slug' => 'existing-store',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/seller/applications', [
            'store_name' => 'Second Store',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_admin_can_approve_seller_application(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $admin->roles()->attach($adminRole->id);

        $sellerUser = User::factory()->create();
        $seller = Seller::create([
            'user_id' => $sellerUser->id,
            'status' => 'pending',
        ]);

        $store = Store::create([
            'seller_id' => $seller->id,
            'name' => 'Pending Store',
            'slug' => 'pending-store',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/seller-applications/{$seller->id}/approve");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Seller application approved',
            ]);

        $this->assertEquals('verified', $seller->fresh()->status);
        $this->assertEquals('active', $store->fresh()->status);
        $this->assertNotNull($seller->fresh()->verified_at);
    }

    public function test_verified_seller_can_update_their_store(): void
    {
        $sellerUser = User::factory()->create();
        $sellerRole = Role::create(['name' => 'Seller', 'slug' => 'seller']);
        $sellerUser->roles()->attach($sellerRole->id);

        $seller = Seller::create([
            'user_id' => $sellerUser->id,
            'status' => 'verified',
            'verified_at' => now(),
        ]);

        $store = Store::create([
            'seller_id' => $seller->id,
            'name' => 'Toko Saya',
            'slug' => 'toko-saya',
            'status' => 'active',
        ]);

        $response = $this->actingAs($sellerUser, 'sanctum')->patchJson('/api/v1/seller/store', [
            'description' => 'Deskripsi baru toko',
            'logo_path' => 'logos/dummy-logo.svg',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.description', 'Deskripsi baru toko');

        $this->assertEquals('Deskripsi baru toko', $store->fresh()->description);
    }
}
