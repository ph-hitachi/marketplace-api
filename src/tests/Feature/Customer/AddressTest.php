<?php

namespace Tests\Feature\Customer;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_list_own_addresses(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $token = auth('api')->login($customer);

        Address::create([
            'user_id'       => $customer->id,
            'label'         => 'Home',
            'phone'         => '09170000000',
            'address_line1' => 'Street address',
            'city'          => 'City',
            'province'      => 'Province',
            'postal_code'   => '1234',
            'country'       => 'Philippines',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/customer/addresses');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['label' => 'Home']);
    }

    public function test_customer_can_create_address(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $token = auth('api')->login($customer);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/customer/addresses', [
                'label'         => 'Work',
                'phone'         => '09171112222',
                'address_line1' => '456 Business Road',
                'city'          => 'Quezon City',
                'province'      => 'Metro Manila',
                'postal_code'   => '1100',
                'country'       => 'Philippines',
                'is_default'    => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('address.label', 'Work');

        $this->assertDatabaseHas('addresses', [
            'user_id'    => $customer->id,
            'label'      => 'Work',
            'is_default' => true,
        ]);
    }

    public function test_unauthorized_customer_view_of_other_users_address(): void
    {
        $customer1 = User::factory()->create(['role' => 'customer']);
        $customer2 = User::factory()->create(['role' => 'customer']);
        $token1 = auth('api')->login($customer1);

        $address2 = Address::create([
            'user_id'       => $customer2->id,
            'label'         => 'Home',
            'address_line1' => 'Street 2',
            'city'          => 'City',
            'province'      => 'Province',
            'postal_code'   => '1234',
            'country'       => 'Philippines',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token1}")
            ->getJson("/api/customer/addresses/{$address2->id}");

        $response->assertStatus(403);
    }

    public function test_customer_can_set_default_address(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $token = auth('api')->login($customer);

        $address1 = Address::create([
            'user_id'       => $customer->id,
            'label'         => 'Address 1',
            'address_line1' => 'Street 1',
            'city'          => 'City',
            'province'      => 'Province',
            'postal_code'   => '1234',
            'country'       => 'Philippines',
            'is_default'    => true,
        ]);

        $address2 = Address::create([
            'user_id'       => $customer->id,
            'label'         => 'Address 2',
            'address_line1' => 'Street 2',
            'city'          => 'City',
            'province'      => 'Province',
            'postal_code'   => '1234',
            'country'       => 'Philippines',
            'is_default'    => false,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/customer/addresses/{$address2->id}/default");

        $response->assertStatus(200);

        // Verify updates
        $this->assertTrue((bool) $address2->fresh()->is_default);
        $this->assertFalse((bool) $address1->fresh()->is_default);
    }
}
