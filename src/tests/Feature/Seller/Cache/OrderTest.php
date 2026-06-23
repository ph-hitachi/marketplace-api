<?php

namespace Tests\Feature\Seller\Cache;

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

    private User $seller1;
    private User $seller2;
    private string $token1;
    private string $token2;
    private Order $order1;
    private Order $order2;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::tags([self::RESOURCE])->flush();

        // Create Seller 1, Shop 1, Product 1, Order 1
        $this->seller1 = User::factory()->create(['role' => 'seller']);
        $this->token1  = auth('api')->login($this->seller1);
        $shop1         = Shop::factory()->create(['user_id' => $this->seller1->id]);
        $product1      = Product::create(['shop_id' => $shop1->id, 'name' => 'Seller 1 Item', 'price' => 100, 'stock' => 10]);

        $customer1 = User::factory()->create(['role' => 'customer']);
        $address1  = Address::create([
            'user_id' => $customer1->id,
            'label' => 'Home',
            'address_line1' => 'St 1',
            'city' => 'City',
            'province' => 'Province',
            'postal_code' => '1234',
            'country' => 'Philippines',
        ]);

        $this->order1 = Order::create([
            'customer_id' => $customer1->id,
            'shop_id' => $shop1->id,
            'address_id' => $address1->id,
            'payment_method' => 'cod',
            'status' => 'pending',
            'total_amount' => 100.00,
        ]);
        $this->order1->items()->create([
            'product_id' => $product1->id,
            'product_name' => $product1->name,
            'unit_price' => $product1->price,
            'quantity' => 1,
            'subtotal' => 100.00,
        ]);

        // Create Seller 2, Shop 2, Product 2, Order 2
        $this->seller2 = User::factory()->create(['role' => 'seller']);
        $this->token2  = auth('api')->login($this->seller2);
        $shop2         = Shop::factory()->create(['user_id' => $this->seller2->id]);
        $product2      = Product::create(['shop_id' => $shop2->id, 'name' => 'Seller 2 Item', 'price' => 200, 'stock' => 5]);

        $customer2 = User::factory()->create(['role' => 'customer']);
        $address2  = Address::create([
            'user_id' => $customer2->id,
            'label' => 'Work',
            'address_line1' => 'St 2',
            'city' => 'City',
            'province' => 'Province',
            'postal_code' => '5678',
            'country' => 'Philippines',
        ]);

        $this->order2 = Order::create([
            'customer_id' => $customer2->id,
            'shop_id' => $shop2->id,
            'address_id' => $address2->id,
            'payment_method' => 'cod',
            'status' => 'pending',
            'total_amount' => 200.00,
        ]);
        $this->order2->items()->create([
            'product_id' => $product2->id,
            'product_name' => $product2->name,
            'unit_price' => $product2->price,
            'quantity' => 1,
            'subtotal' => 200.00,
        ]);

        // Default back to Seller 1 context
        auth('api')->login($this->seller1);
    }

    // ── Listing ───────────────────────────────────────────────────────────────

    public function test_seller_order_listing_returns_miss_on_first_request(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson('/api/seller/orders');

        $response->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'MISS');

        $orderIds = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($this->order1->id, $orderIds);
        $this->assertStringNotContainsString('__PHP_Incomplete_Class_Name', $response->getContent());
    }

    public function test_seller_order_listing_returns_hit_on_second_request(): void
    {
        $first = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson('/api/seller/orders');

        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson('/api/seller/orders');

        $response->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'HIT');

        $this->assertEquals($first->json(), $response->json());
        $orderIds = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($this->order1->id, $orderIds);
        $this->assertStringNotContainsString('__PHP_Incomplete_Class_Name', $response->getContent());
    }

    // ── Detail ────────────────────────────────────────────────────────────────

    public function test_seller_order_detail_returns_miss_on_first_request(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson("/api/seller/orders/{$this->order1->id}");

        $response->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'MISS')
            ->assertJsonPath('order.id', $this->order1->id);

        $this->assertStringNotContainsString('__PHP_Incomplete_Class_Name', $response->getContent());
    }

    public function test_seller_order_detail_returns_hit_on_second_request(): void
    {
        $first = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson("/api/seller/orders/{$this->order1->id}");

        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson("/api/seller/orders/{$this->order1->id}");

        $response->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'HIT')
            ->assertJsonPath('order.id', $this->order1->id);

        $this->assertEquals($first->json(), $response->json());
        $this->assertStringNotContainsString('__PHP_Incomplete_Class_Name', $response->getContent());
    }

    // ── Invalidation ──────────────────────────────────────────────────────────

    public function test_seller_order_listing_cache_is_flushed_when_seller_updates_status(): void
    {
        $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson('/api/seller/orders')
            ->assertHeader('X-Cache-Status', 'MISS');

        $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->patchJson("/api/seller/orders/{$this->order1->id}/status", ['status' => 'shipped'])
            ->assertStatus(200);

        $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson('/api/seller/orders')
            ->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'MISS');
    }

    public function test_seller_order_detail_cache_is_flushed_when_seller_updates_status(): void
    {
        $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson("/api/seller/orders/{$this->order1->id}")
            ->assertHeader('X-Cache-Status', 'MISS');

        $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->patchJson("/api/seller/orders/{$this->order1->id}/status", ['status' => 'shipped'])
            ->assertStatus(200);

        $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson("/api/seller/orders/{$this->order1->id}")
            ->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'MISS');
    }

    // ── Intra-role Isolation ─────────────────────────────────────────────────

    public function test_seller_a_does_not_leak_to_seller_b(): void
    {
        // Seller 1 warms their list cache
        $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson('/api/seller/orders')
            ->assertHeader('X-Cache-Status', 'MISS');

        // Switch guard context to Seller 2
        auth('api')->login($this->seller2);

        // Seller 2 hits listing — must be MISS because listing key encodes shop ID
        $response = $this->withHeader('Authorization', "Bearer {$this->token2}")
            ->getJson('/api/seller/orders');

        $response->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'MISS');

        // Seller 2's request should only return Seller 2's order, not Seller 1's
        $orderIds = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($this->order2->id, $orderIds);
        $this->assertNotContains($this->order1->id, $orderIds);
    }
}
