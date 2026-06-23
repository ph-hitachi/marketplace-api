<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProductCacheTest extends TestCase
{
    use RefreshDatabase;

    private const RESOURCE = Product::class;

    private User $user;
    private Shop $shop;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::tags([self::RESOURCE])->flush();

        $this->user    = User::factory()->create();
        $this->shop    = Shop::factory()->create(['user_id' => $this->user->id]);
        $this->product = Product::factory()->create([
            'shop_id'   => $this->shop->id,
            'name'      => 'Cache Test Product',
            'is_active' => true,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function detailKey(): string
    {
        $query = Product::available()
            ->with('shop')
            ->where('id', (string) $this->product->id);
        return md5($query->toRawSql());
    }

    private function listingKey(int $page = 1, int $perPage = 15): string
    {
        $query = Product::available()
            ->with('shop')
            ->latest()
            ->forPage($page, $perPage);
        return md5($query->toRawSql());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Listing cache (Cache-Aside)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_product_listing_is_cached_after_first_request(): void
    {
        Cache::tags([self::RESOURCE])->flush();

        $this->assertFalse(
            Cache::tags([self::RESOURCE])->has($this->listingKey())
        );

        $this->getJson('/api/products')->assertStatus(200);

        $this->assertTrue(
            Cache::tags([self::RESOURCE])->has($this->listingKey())
        );
    }

    public function test_product_listing_is_served_from_cache_on_subsequent_requests(): void
    {
        $this->getJson('/api/products')->assertStatus(200);

        // Poison the cache — response should still return the stale cached value
        $poisoned = ['data' => [['name' => 'Stale Cached Product']]];
        Cache::tags([self::RESOURCE])->put($this->listingKey(), $poisoned, 60);

        $response = $this->getJson('/api/products')->assertStatus(200);
        $this->assertEquals($poisoned, $response->json());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Detail cache (Cache-Aside)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_get_product_detail_is_cached_after_first_request(): void
    {
        Cache::tags([self::RESOURCE])->flush();

        $this->assertFalse(
            Cache::tags([self::RESOURCE])->has($this->detailKey())
        );

        $this->getJson("/api/products/{$this->product->id}")->assertStatus(200);

        $this->assertTrue(
            Cache::tags([self::RESOURCE])->has($this->detailKey())
        );
    }

    public function test_detail_cache_is_flushed_on_product_update(): void
    {
        // Warm detail cache
        $this->getJson("/api/products/{$this->product->id}")->assertStatus(200);
        $this->assertTrue(
            Cache::tags([self::RESOURCE])->has($this->detailKey())
        );

        $this->product->update(['name' => 'Updated Product Name']);

        // Cache must be flushed (cleared)
        $this->assertFalse(
            Cache::tags([self::RESOURCE])->has($this->detailKey())
        );
    }

    public function test_detail_cache_is_removed_when_product_is_deleted(): void
    {
        // Warm detail cache
        $this->getJson("/api/products/{$this->product->id}")->assertStatus(200);
        $this->assertTrue(
            Cache::tags([self::RESOURCE])->has($this->detailKey())
        );

        $this->product->delete();

        $this->assertFalse(
            Cache::tags([self::RESOURCE])->has($this->detailKey())
        );
    }

    public function test_detail_cache_is_flushed_when_product_is_restored(): void
    {
        $this->product->delete();

        // Warm cache manually for the soft-deleted product
        Cache::tags([self::RESOURCE])->put($this->detailKey(), ['id' => $this->product->id], 60);
        $this->assertTrue(Cache::tags([self::RESOURCE])->has($this->detailKey()));

        $this->product->restore();

        $this->assertFalse(
            Cache::tags([self::RESOURCE])->has($this->detailKey())
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Listing invalidation
    // ──────────────────────────────────────────────────────────────────────────

    public function test_listing_cache_is_flushed_on_product_update(): void
    {
        // Warm the listing cache
        $this->getJson('/api/products')->assertStatus(200);
        $this->assertTrue(
            Cache::tags([self::RESOURCE])->has($this->listingKey())
        );

        $this->product->update(['name' => 'New Name Triggers Flush']);

        // All slices must be gone
        $this->assertFalse(
            Cache::tags([self::RESOURCE])->has($this->listingKey())
        );
    }

    public function test_listing_cache_is_flushed_on_product_delete(): void
    {
        $this->getJson('/api/products')->assertStatus(200);
        $this->assertTrue(
            Cache::tags([self::RESOURCE])->has($this->listingKey())
        );

        $this->product->delete();

        $this->assertFalse(
            Cache::tags([self::RESOURCE])->has($this->listingKey())
        );
    }
}
