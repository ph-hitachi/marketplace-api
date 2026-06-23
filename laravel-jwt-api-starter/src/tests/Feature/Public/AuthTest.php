<?php

namespace Tests\Feature\Public;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_registration_success(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Customer Test',
            'email'                 => 'customertest@marketplace.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role'                  => 'customer',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email', 'role'],
                'authorization' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'customertest@marketplace.com',
            'role'  => 'customer',
        ]);
    }

    public function test_seller_registration_success(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Seller Test',
            'email'                 => 'sellertest@marketplace.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role'                  => 'seller',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'sellertest@marketplace.com')->first();
        $this->assertNotNull($user);

        $this->assertDatabaseHas('users', [
            'email' => 'sellertest@marketplace.com',
            'role'  => 'seller',
        ]);
    }

    public function test_registration_validation_failures(): void
    {
        // Missing fields
        $response = $this->postJson('/api/auth/register', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'role']);

        // Password mismatch and short
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'pwd',
            'password_confirmation' => 'diff',
            'role'                  => 'customer',
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_cannot_register_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'duplicate@example.com',
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'New User',
            'email'                 => 'duplicate@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role'                  => 'customer',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_success(): void
    {
        $user = User::factory()->create([
            'email'    => 'login@example.com',
            'password' => bcrypt('Secret123!'),
            'role'     => 'customer',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'login@example.com',
            'password' => 'Secret123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user',
                'authorization' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                ],
            ]);
    }

    public function test_login_deactivated_account_fail(): void
    {
        $user = User::factory()->create([
            'email'     => 'inactive@example.com',
            'password'  => bcrypt('Secret123!'),
            'role'      => 'customer',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'inactive@example.com',
            'password' => 'Secret123!',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'error_code' => 'ACCOUNT_DEACTIVATED',
            ]);
    }

    public function test_login_invalid_credentials_fail(): void
    {
        $user = User::factory()->create([
            'email'    => 'login@example.com',
            'password' => bcrypt('Secret123!'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'login@example.com',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                 'error_code' => 'INVALID_CREDENTIALS',
                 'message'    => 'The email or password you entered is incorrect.',
            ]);
    }

    public function test_logout_success(): void
    {
        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout');

        $response->assertStatus(200);
        $this->assertFalse(auth('api')->check());
    }

    public function test_me_profile_unauthorized_rejection(): void
    {
        $response = $this->getJson('/api/user/me');
        $response->assertStatus(401);
    }

    public function test_me_profile_success(): void
    {
        $user  = User::factory()->create(['role' => 'customer']);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user/me');

        $response->assertStatus(200)
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_cannot_register_admin_role(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Admin Hack',
            'email'                 => 'adminhack@marketplace.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role'                  => 'admin', // Forbidden role for registration
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }
}
