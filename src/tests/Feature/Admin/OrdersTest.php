<?php

namespace Tests\Feature\Admin;

use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $token;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->token = auth('api')->login($this->admin);

        $customer = User::factory()->create(['role' => 'customer']);
        $seller   = User::factory()->create(['role' => 'seller']);
        $shop     = \App\Models\Shop::factory()->create(['user_id' => $seller->id]);

        $address = Address::create([
            'user_id'       => $customer->id,
            'label'         => 'Home',
            'address_line1' => 'St',
            'city'          => 'City',
            'province'      => 'Province',
            'postal_code'   => '1234',
            'country'       => 'Philippines',
        ]);

        $product = Product::create(['shop_id' => $shop->id, 'name' => 'Test', 'price' => 120, 'stock' => 10]);
        $this->order = Order::create([
            'customer_id'    => $customer->id,
            'shop_id'        => $shop->id,
            'address_id'     => $address->id,
            'payment_method' => 'cod',
            'status'         => 'pending',
            'total_amount'   => 120.00,
        ]);
        $this->order->items()->create([
            'product_id'     => $product->id,
            'product_name'   => $product->name,
            'unit_price'     => $product->price,
            'quantity'       => 1,
            'subtotal'       => 120.00,
        ]);
    }

    public function test_admin_can_list_all_orders(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/admin/orders');

        $response->assertStatus(200)
            ->assertJsonStructure(['current_page', 'data']);
    }

    public function test_admin_can_view_order_details(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/admin/orders/{$this->order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('order.total_amount', '120.00');
    }

    public function test_admin_cannot_override_order_status(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/admin/orders/{$this->order->id}/status", [
                'status' => 'delivered',
            ]);
        $response->assertStatus(404);
    }
}
