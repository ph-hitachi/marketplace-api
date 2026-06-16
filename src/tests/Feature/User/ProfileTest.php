<?php

namespace Tests\Feature\User;

use App\Models\User;
use App\Models\SellerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_profile_successfully(): void
    {
        $user = User::factory()->create([
            'name'  => 'Old Name',
            'email' => 'old@example.com',
            'role'  => 'customer',
        ]);
        $token = $user->createToken('token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/user/profile', [
                'name'                  => 'New Name',
                'email'                 => 'new@example.com',
                'password'              => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.name', 'New Name')
            ->assertJsonPath('user.email', 'new@example.com');

        $this->assertDatabaseHas('users', [
            'id'    => $user->id,
            'name'  => 'New Name',
            'email' => 'new@example.com',
        ]);
    }

    public function test_user_cannot_update_profile_role(): void
    {
        $user = User::factory()->create([
            'name'  => 'Customer User',
            'email' => 'customer@example.com',
            'role'  => 'customer',
        ]);
        $token = $user->createToken('token')->plainTextToken;

        // Attempting to change role to seller or admin
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/user/profile', [
                'name'  => 'Customer User',
                'email' => 'customer@example.com',
                'role'  => 'seller', // Trying to change role
            ]);

        $response->assertStatus(200);

        // Verify that user's role remains 'customer' in database
        $this->assertDatabaseHas('users', [
            'id'   => $user->id,
            'role' => 'customer',
        ]);

        $this->assertDatabaseMissing('users', [
            'id'   => $user->id,
            'role' => 'seller',
        ]);
    }

    public function test_seller_can_update_shop_profile_successfully(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $token  = $seller->createToken('token')->plainTextToken;

        // Populate initial profile
        $seller->sellerProfile()->create([
            'shop_name'        => 'Old Shop Name',
            'shop_description' => 'Old description.',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/seller/profile', [
                'shop_name'        => 'New Shop Name',
                'shop_description' => 'New description.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('profile.shop_name', 'New Shop Name')
            ->assertJsonPath('profile.shop_description', 'New description.');

        $this->assertDatabaseHas('seller_profiles', [
            'user_id'          => $seller->id,
            'shop_name'        => 'New Shop Name',
            'shop_description' => 'New description.',
        ]);
    }

    public function test_customer_cannot_update_seller_profile(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $token    = $customer->createToken('token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/seller/profile', [
                'shop_name'        => 'Hacked Shop',
                'shop_description' => 'Should not work.',
            ]);

        $response->assertStatus(403);
    }

    public function test_seller_shop_name_unique_validation(): void
    {
        // Seller A
        $sellerA = User::factory()->create(['role' => 'seller']);
        $sellerA->sellerProfile()->create([
            'shop_name' => 'Unique Shop A',
        ]);

        // Seller B
        $sellerB = User::factory()->create(['role' => 'seller']);
        $sellerB->sellerProfile()->create([
            'shop_name' => 'Unique Shop B',
        ]);
        $tokenB = $sellerB->createToken('token')->plainTextToken;

        // Seller B tries to take Seller A's shop name
        $response = $this->withHeader('Authorization', "Bearer {$tokenB}")
            ->putJson('/api/seller/profile', [
                'shop_name'        => 'Unique Shop A',
                'shop_description' => 'Attempted hijack.',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shop_name']);

        // Seller B updates shop description but keeps own unique name
        $response2 = $this->withHeader('Authorization', "Bearer {$tokenB}")
            ->putJson('/api/seller/profile', [
                'shop_name'        => 'Unique Shop B',
                'shop_description' => 'Valid update.',
            ]);

        $response2->assertStatus(200);
    }
}
