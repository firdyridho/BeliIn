<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_works_with_both_login_and_email_fields(): void
    {
        $user = User::factory()->create([
            'email' => 'buyer@beliin.test',
            'phone' => '081298765432',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        // 1. Login with 'email' field
        $resEmail = $this->postJson('/api/v1/auth/login', [
            'email' => 'buyer@beliin.test',
            'password' => 'password',
        ]);
        $resEmail->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'user']]);

        // 2. Login with 'login' field
        $resLogin = $this->postJson('/api/v1/auth/login', [
            'login' => 'buyer@beliin.test',
            'password' => 'password',
        ]);
        $resLogin->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'user']]);

        // 3. Login with 'phone' as login
        $resPhone = $this->postJson('/api/v1/auth/login', [
            'phone' => '081298765432',
            'password' => 'password',
        ]);
        $resPhone->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'user']]);
    }
}
