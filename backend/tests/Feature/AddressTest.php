<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Auth\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_address(): void
    {
        $user = User::factory()->create();

        $payload = [
            'recipient_name' => 'Budi Alamat',
            'phone' => '081234567890',
            'address_line' => 'Jl. Merdeka No. 45',
            'province' => 'DKI Jakarta',
            'city' => 'Jakarta Selatan',
            'district' => 'Kebayoran Baru',
            'postal_code' => '12160',
            'is_default' => true,
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/addresses', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Address created successfully',
            ])
            ->assertJsonPath('data.recipient_name', 'Budi Alamat')
            ->assertJsonPath('data.is_default', true);

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $user->id,
            'recipient_name' => 'Budi Alamat',
            'is_default' => 1,
        ]);
    }

    public function test_user_can_list_their_addresses(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        UserAddress::create([
            'user_id' => $user->id,
            'recipient_name' => 'Home',
            'phone' => '081234567890',
            'address_line' => 'Home address',
            'province' => 'Jawa Barat',
            'city' => 'Bandung',
            'district' => 'Coblong',
            'postal_code' => '40132',
            'is_default' => true,
        ]);

        UserAddress::create([
            'user_id' => $otherUser->id,
            'recipient_name' => 'Other Address',
            'phone' => '081234567891',
            'address_line' => 'Other street',
            'province' => 'Jawa Barat',
            'city' => 'Bandung',
            'district' => 'Coblong',
            'postal_code' => '40132',
            'is_default' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/addresses');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_set_default_address(): void
    {
        $user = User::factory()->create();

        $addr1 = UserAddress::create([
            'user_id' => $user->id,
            'recipient_name' => 'Addr 1',
            'phone' => '081234567890',
            'address_line' => 'Addr 1 line',
            'province' => 'Jawa Barat',
            'city' => 'Bandung',
            'district' => 'Coblong',
            'postal_code' => '40132',
            'is_default' => true,
        ]);

        $addr2 = UserAddress::create([
            'user_id' => $user->id,
            'recipient_name' => 'Addr 2',
            'phone' => '081234567890',
            'address_line' => 'Addr 2 line',
            'province' => 'Jawa Barat',
            'city' => 'Bandung',
            'district' => 'Coblong',
            'postal_code' => '40132',
            'is_default' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/v1/addresses/{$addr2->id}/default");

        $response->assertStatus(200);

        $this->assertFalse((bool) $addr1->fresh()->is_default);
        $this->assertTrue((bool) $addr2->fresh()->is_default);
    }
}
