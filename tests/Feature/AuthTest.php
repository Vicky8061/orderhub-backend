<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'admin@orderhub.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'token',
                'user' => ['id', 'name', 'email', 'role', 'is_active'],
            ])
            ->assertJsonPath('user.role', 'admin');
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'admin@orderhub.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_deactivated_user_cannot_login(): void
    {
        User::where('email', 'cashier@orderhub.com')->update(['is_active' => false]);

        $response = $this->postJson('/api/login', [
            'email' => 'cashier@orderhub.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);

        // Reset for subsequent tests
        User::where('email', 'cashier@orderhub.com')->update(['is_active' => true]);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::where('email', 'kitchen@orderhub.com')->first();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonPath('user.email', 'kitchen@orderhub.com')
            ->assertJsonPath('user.role', 'kitchen');
    }

    public function test_user_can_logout(): void
    {
        $user = User::where('email', 'admin@orderhub.com')->first();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully']);
    }
}
