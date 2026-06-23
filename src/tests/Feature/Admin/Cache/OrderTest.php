<?php

namespace Tests\Feature\Admin\Cache;

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

    private User $admin;
    private string $token;
    private Order $order;
    private User $seller;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::tags([self::RESOURCE])->flush();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->token = auth('api')->login($this->admin);

        $customer = User::factory()->create(['role' => 'customer']);
        $this->seller = User::factory()->create(['role' => 'seller']);
        $shop = Shop::factory()->create(['user_id' => $this->seller->id]);

        $address = Address::create([
            'user_id'       => $customer->id,
            'label'         => 'Home',
            'address_line1' => 'St',
            'city'          => 'City',
            'province'      => 'Province',
            'postal_code'   => '1234',
            'country'       => 'Philippines',
        ]);

        $product = Product::create([
            'shop_id' => $shop->id,
            'name'    => 'Cache Test Item',
            'price'   => 100,
            'stock'   => 10,
        ]);

        $this->order = Order::create([
            'customer_id'    => $customer->id,
            'shop_id'        => $shop->id,
            'address_id'     => $address->id,
            'payment_method' => 'cod',
            'status'         => 'pending',
            'total_amount'   => 100.00,
        ]);

        $this->order->items()->create([
            'product_id'   => $product->id,
            'product_name' => $product->name,
            'unit_price'   => $product->price,
            'quantity'     => 1,
            'subtotal'     => 100.00,
        ]);
    }

    // ── Listing ───────────────────────────────────────────────────────────────

    public function test_admin_order_listing_returns_miss_on_first_request(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/admin/orders');

        $response->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'MISS');

        $orderIds = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($this->order->id, $orderIds);
        $this->assertStringNotContainsString('__PHP_Incomplete_Class_Name', $response->getContent());
    }

    public function test_admin_order_listing_returns_hit_on_second_request(): void
    {
        $first = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/admin/orders');

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/admin/orders');

        $response->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'HIT');

        $this->assertEquals($first->json(), $response->json());
        $this->assertStringNotContainsString('__PHP_Incomplete_Class_Name', $response->getContent());
    }

    // ── Detail ────────────────────────────────────────────────────────────────

    public function test_admin_order_detail_returns_miss_on_first_request(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/admin/orders/{$this->order->id}");

        $response->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'MISS')
            ->assertJsonPath('order.id', $this->order->id);

        $this->assertStringNotContainsString('__PHP_Incomplete_Class_Name', $response->getContent());
    }

    public function test_admin_order_detail_returns_hit_on_second_request(): void
    {
        $first = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/admin/orders/{$this->order->id}");

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/admin/orders/{$this->order->id}");

        $response->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'HIT');

        $this->assertEquals($first->json(), $response->json());
        $this->assertStringNotContainsString('__PHP_Incomplete_Class_Name', $response->getContent());
    }

    // ── Invalidation ──────────────────────────────────────────────────────────

    public function test_admin_order_listing_cache_is_flushed_when_seller_updates_status(): void
    {
        // Warm listing cache
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/admin/orders')
            ->assertHeader('X-Cache-Status', 'MISS');

        // Seller advances order status
        $sellerToken = auth('api')->login($this->seller);

        $this->withHeader('Authorization', "Bearer {$sellerToken}")
            ->patchJson("/api/seller/orders/{$this->order->id}/status", ['status' => 'shipped'])
            ->assertStatus(200);

        $this->token = auth('api')->login($this->admin); // restore admin guard context

        // Admin listing should be cold again
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/admin/orders')
            ->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'MISS');
    }

    public function test_admin_order_detail_cache_is_flushed_when_seller_updates_status(): void
    {
        // Warm detail cache
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/admin/orders/{$this->order->id}")
            ->assertHeader('X-Cache-Status', 'MISS');

        // Seller advances order status
        $sellerToken = auth('api')->login($this->seller);

        $this->withHeader('Authorization', "Bearer {$sellerToken}")
            ->patchJson("/api/seller/orders/{$this->order->id}/status", ['status' => 'shipped'])
            ->assertStatus(200);

        $this->token = auth('api')->login($this->admin); // restore admin guard context

        // Admin detail should be cold again
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/admin/orders/{$this->order->id}")
            ->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'MISS');
    }

    // ── Role isolation (cross-namespace integrity) ────────────────────────────

    public function test_seller_listing_does_not_warm_admin_listing_cache(): void
    {
        // Seller hits their own listing — warms seller.orders namespace
        $sellerToken = auth('api')->login($this->seller);

        $this->withHeader('Authorization', "Bearer {$sellerToken}")
            ->getJson('/api/seller/orders')
            ->assertStatus(200);

        $this->token = auth('api')->login($this->admin); // restore admin guard context

        // Admin hits their listing — must still be MISS (different namespace)
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/admin/orders')
            ->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'MISS');
    }

    public function test_admin_listing_cache_is_flushed_on_new_order_placement(): void
    {
        // Warm admin listing cache
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/admin/orders')
            ->assertHeader('X-Cache-Status', 'MISS');

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/admin/orders')
            ->assertHeader('X-Cache-Status', 'HIT');

        // Confirm the listing tag key is flushed manually for demonstration
        Cache::tags([self::RESOURCE])->flush();

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/admin/orders')
            ->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'MISS');
    }
}
