<?php

namespace Tests\Feature\User;

use App\Models\User;
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
        $token = auth('api')->login($user);

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
        $token = auth('api')->login($user);

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
}
