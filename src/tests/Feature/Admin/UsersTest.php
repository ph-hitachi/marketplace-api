<?php

namespace Tests\Feature\Admin;

use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $token;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->token = $this->admin->createToken('token')->plainTextToken;

        $this->customer = User::factory()->create(['role' => 'customer']);
    }

    public function test_admin_can_list_all_users(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/admin/users');

        $response->assertStatus(200)
            ->assertJsonStructure(['current_page', 'data']);
    }

    public function test_admin_can_view_user_details(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/admin/users/{$this->customer->id}");

        $response->assertStatus(200)
            ->assertJsonPath('user.email', $this->customer->email);
    }

    public function test_admin_can_deactivate_user_and_revoke_tokens(): void
    {
        // Give customer a token
        $customerToken = $this->customer->createToken('c_token')->plainTextToken;
        $this->assertCount(1, $this->customer->tokens);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/admin/users/{$this->customer->id}/deactivate");

        $response->assertStatus(200);

        // Verify deactivated in DB
        $this->assertFalse((bool) $this->customer->fresh()->is_active);

        // Verify tokens deleted
        $this->assertCount(0, $this->customer->fresh()->tokens);

        // Verify customer cannot use revoked token
        $this->app['auth']->forgetGuards();
        $responseAuthCheck = $this->withHeader('Authorization', "Bearer {$customerToken}")
            ->getJson('/api/user/wallets');
        $responseAuthCheck->assertStatus(401);
    }

    public function test_admin_can_activate_user(): void
    {
        $this->customer->update(['is_active' => false]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/admin/users/{$this->customer->id}/activate");

        $response->assertStatus(200);
        $this->assertTrue((bool) $this->customer->fresh()->is_active);
    }

    public function test_admin_cannot_delete_user_with_active_orders(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $address = Address::create([
            'user_id'       => $this->customer->id,
            'label'         => 'Home',
            'address_line1' => 'St',
            'city'          => 'City',
            'province'      => 'Province',
            'postal_code'   => '1234',
            'country'       => 'Philippines',
        ]);

        $product = Product::create(['seller_id' => $seller->id, 'name' => 'Test', 'price' => 50, 'stock' => 10]);
        $order = Order::create([
            'customer_id'    => $this->customer->id,
            'seller_id'      => $seller->id,
            'address_id'     => $address->id,
            'payment_method' => 'cod',
            'status'         => 'pending',
            'total_amount'   => 50.00,
        ]);
        $order->items()->create([
            'product_id'     => $product->id,
            'product_name'   => $product->name,
            'unit_price'     => $product->price,
            'quantity'       => 1,
            'subtotal'       => 50.00,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/admin/users/{$this->customer->id}");

        $response->assertStatus(409)
            ->assertJson([
                'error_code' => 'DELETE_BLOCKED',
            ]);

        $this->assertDatabaseHas('users', ['id' => $this->customer->id]);
    }

    public function test_admin_can_delete_user_without_active_orders(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/admin/users/{$this->customer->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $this->customer->id]);
    }
}
