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
            'role'  => 'user',
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
            'name'  => 'Standard User',
            'email' => 'user@example.com',
            'role'  => 'user',
        ]);
        $token = auth('api')->login($user);

        // Attempting to change role to admin
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/user/profile', [
                'name'  => 'Standard User',
                'email' => 'user@example.com',
                'role'  => 'admin', // Trying to change role to admin
            ]);

        $response->assertStatus(200);

        // Verify that user's role remains 'user' in database
        $this->assertDatabaseHas('users', [
            'id'   => $user->id,
            'role' => 'user',
        ]);

        $this->assertDatabaseMissing('users', [
            'id'   => $user->id,
            'role' => 'admin',
        ]);
    }

    public function test_user_can_update_password_successfully(): void
    {
        $user = User::factory()->create([
            'role'     => 'user',
            'password' => 'OldPassword123!',
        ]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/user/password', [
                'current_password'      => 'OldPassword123!',
                'password'              => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertStatus(200);

        // Verify user can login with new password
        $loginResponse = $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'NewPassword123!',
        ]);
        $loginResponse->assertStatus(200);
    }
}
