<?php

namespace Tests\Feature\Customer\Cache;

use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private const RESOURCE = Order::class;

    private User $customer1;
    private User $customer2;
    private string $token1;
    private string $token2;
    private Order $order1;
    private Order $order2;
    private Product $product;
    private Address $address1;
    private Address $address2;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::tags([self::RESOURCE])->flush();

        // Create Sellers
        $seller = User::factory()->create(['role' => 'seller']);
        $shop   = Shop::factory()->create(['user_id' => $seller->id]);
        $this->product = Product::create(['shop_id' => $shop->id, 'name' => 'Cache Item', 'price' => 100, 'stock' => 10, 'is_active' => true]);

        // Create Customer 1, Address 1, Order 1
        $this->customer1 = User::factory()->create(['role' => 'customer']);
        $this->token1    = auth('api')->login($this->customer1);
        $this->address1  = Address::create([
            'user_id' => $this->customer1->id,
            'label' => 'Home',
            'address_line1' => 'St 1',
            'city' => 'City',
            'province' => 'Province',
            'postal_code' => '1234',
            'country' => 'Philippines',
        ]);

        $this->order1 = Order::create([
            'customer_id' => $this->customer1->id,
            'shop_id' => $shop->id,
            'address_id' => $this->address1->id,
            'payment_method' => 'cod',
            'status' => 'pending',
            'total_amount' => 100.00,
        ]);
        $this->order1->items()->create([
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'unit_price' => $this->product->price,
            'quantity' => 1,
            'subtotal' => 100.00,
        ]);

        // Create Customer 2, Address 2, Order 2
        $this->customer2 = User::factory()->create(['role' => 'customer']);
        $this->token2    = auth('api')->login($this->customer2);
        $this->address2  = Address::create([
            'user_id' => $this->customer2->id,
            'label' => 'Work',
            'address_line1' => 'St 2',
            'city' => 'City',
            'province' => 'Province',
            'postal_code' => '5678',
            'country' => 'Philippines',
        ]);

        $this->order2 = Order::create([
            'customer_id' => $this->customer2->id,
            'shop_id' => $shop->id,
            'address_id' => $this->address2->id,
            'payment_method' => 'cod',
            'status' => 'pending',
            'total_amount' => 200.00,
        ]);
        $this->order2->items()->create([
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'unit_price' => $this->product->price,
            'quantity' => 2,
            'subtotal' => 200.00,
        ]);

        // Default back to Customer 1 context
        auth('api')->login($this->customer1);
    }

    // ── Listing ───────────────────────────────────────────────────────────────

    public function test_customer_order_listing_returns_miss_on_first_request(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson('/api/customer/orders');

        $response->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'MISS');

        $orderIds = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($this->order1->id, $orderIds);
        $this->assertStringNotContainsString('__PHP_Incomplete_Class_Name', $response->getContent());
    }

    public function test_customer_order_listing_returns_hit_on_second_request(): void
    {
        $first = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson('/api/customer/orders');

        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson('/api/customer/orders');

        $response->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'HIT');

        $this->assertEquals($first->json(), $response->json());
        $orderIds = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($this->order1->id, $orderIds);
        $this->assertStringNotContainsString('__PHP_Incomplete_Class_Name', $response->getContent());
    }

    // ── Detail ────────────────────────────────────────────────────────────────

    public function test_customer_order_detail_returns_miss_on_first_request(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson("/api/customer/orders/{$this->order1->id}");

        $response->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'MISS')
            ->assertJsonPath('order.id', $this->order1->id);

        $this->assertStringNotContainsString('__PHP_Incomplete_Class_Name', $response->getContent());
    }

    public function test_customer_order_detail_returns_hit_on_second_request(): void
    {
        $first = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson("/api/customer/orders/{$this->order1->id}");

        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson("/api/customer/orders/{$this->order1->id}");

        $response->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'HIT')
            ->assertJsonPath('order.id', $this->order1->id);

        $this->assertEquals($first->json(), $response->json());
        $this->assertStringNotContainsString('__PHP_Incomplete_Class_Name', $response->getContent());
    }

    // ── Invalidation ──────────────────────────────────────────────────────────

    public function test_customer_order_listing_cache_is_flushed_when_customer_cancels(): void
    {
        $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson('/api/customer/orders')
            ->assertHeader('X-Cache-Status', 'MISS');

        $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->patchJson("/api/customer/orders/{$this->order1->id}/cancel", ['cancel_reason' => 1])
            ->assertStatus(200);

        $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson('/api/customer/orders')
            ->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'MISS');
    }

    public function test_customer_order_detail_cache_is_flushed_when_customer_cancels(): void
    {
        $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson("/api/customer/orders/{$this->order1->id}")
            ->assertHeader('X-Cache-Status', 'MISS');

        $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->patchJson("/api/customer/orders/{$this->order1->id}/cancel", ['cancel_reason' => 1])
            ->assertStatus(200);

        $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson("/api/customer/orders/{$this->order1->id}")
            ->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'MISS');
    }

    public function test_customer_order_listing_cache_is_flushed_when_customer_confirms(): void
    {
        $this->order1->update(['status' => 'delivered']);

        $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson('/api/customer/orders')
            ->assertHeader('X-Cache-Status', 'MISS');

        $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->postJson("/api/customer/orders/{$this->order1->id}/confirm")
            ->assertStatus(200);

        $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson('/api/customer/orders')
            ->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'MISS');
    }

    public function test_customer_order_listing_cache_is_flushed_on_new_order(): void
    {
        $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson('/api/customer/orders')
            ->assertHeader('X-Cache-Status', 'MISS');

        // Place new order
        $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->postJson('/api/customer/orders', [
                'address_id' => $this->address1->id,
                'payment_method' => 'cod',
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 1]
                ]
            ])
            ->assertStatus(201);

        $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson('/api/customer/orders')
            ->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'MISS');
    }

    // ── Intra-role Isolation ─────────────────────────────────────────────────

    public function test_customer_a_does_not_leak_to_customer_b(): void
    {
        // Customer 1 warms their list cache
        $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson('/api/customer/orders')
            ->assertHeader('X-Cache-Status', 'MISS');

        // Switch guard context to Customer 2
        auth('api')->login($this->customer2);

        // Customer 2 hits listing — must be MISS because listing key encodes customer user ID
        $response = $this->withHeader('Authorization', "Bearer {$this->token2}")
            ->getJson('/api/customer/orders');

        $response->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'MISS');

        // Customer 2's request should only return Customer 2's order, not Customer 1's
        $orderIds = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($this->order2->id, $orderIds);
        $this->assertNotContains($this->order1->id, $orderIds);
    }
}
