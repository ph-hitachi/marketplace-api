<?php

namespace Tests\Feature\Admin\Cache;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UsersTest extends TestCase
{
    use RefreshDatabase;

    private const RESOURCE = User::class;

    private User $admin;
    private string $token;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::tags([self::RESOURCE])->flush();

        $this->admin    = User::factory()->create(['role' => 'admin']);
        $this->token    = auth('api')->login($this->admin);
        $this->customer = User::factory()->create(['role' => 'customer']);
    }

    // ── Listing ───────────────────────────────────────────────────────────────

    public function test_admin_user_listing_returns_miss_on_first_request(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/admin/users');

        $response->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'MISS');

        $userIds = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($this->customer->id, $userIds);
        $this->assertStringNotContainsString('__PHP_Incomplete_Class_Name', $response->getContent());
    }

    public function test_admin_user_listing_returns_hit_on_second_request(): void
    {
        $first = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/admin/users');

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/admin/users');

        $response->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'HIT');

        $this->assertEquals($first->json(), $response->json());
        $this->assertStringNotContainsString('__PHP_Incomplete_Class_Name', $response->getContent());
    }

    // ── Detail ────────────────────────────────────────────────────────────────

    public function test_admin_user_detail_returns_miss_on_first_request(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/admin/users/{$this->customer->id}");

        $response->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'MISS')
            ->assertJsonPath('user.id', $this->customer->id);

        $this->assertStringNotContainsString('__PHP_Incomplete_Class_Name', $response->getContent());
    }

    public function test_admin_user_detail_returns_hit_on_second_request(): void
    {
        $first = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/admin/users/{$this->customer->id}");

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/admin/users/{$this->customer->id}");

        $response->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'HIT');

        $this->assertEquals($first->json(), $response->json());
        $this->assertStringNotContainsString('__PHP_Incomplete_Class_Name', $response->getContent());
    }

    // ── Invalidation on mutations ─────────────────────────────────────────────

    public function test_user_detail_cache_is_invalidated_on_deactivate(): void
    {
        // Warm detail cache
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/admin/users/{$this->customer->id}")
            ->assertHeader('X-Cache-Status', 'MISS');

        // Deactivate the user
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/admin/users/{$this->customer->id}/deactivate")
            ->assertStatus(204);

        // Detail must be cold after mutation
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/admin/users/{$this->customer->id}")
            ->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'MISS');
    }

    public function test_user_detail_cache_is_invalidated_on_activate(): void
    {
        $this->customer->update(['is_active' => false]);

        // Warm detail cache
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/admin/users/{$this->customer->id}")
            ->assertHeader('X-Cache-Status', 'MISS');

        // Activate the user
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/admin/users/{$this->customer->id}/activate")
            ->assertStatus(204);

        // Detail must be cold after mutation
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/admin/users/{$this->customer->id}")
            ->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'MISS');
    }

    public function test_user_detail_cache_is_invalidated_on_delete(): void
    {
        // Warm detail cache
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/admin/users/{$this->customer->id}")
            ->assertHeader('X-Cache-Status', 'MISS');

        // Delete the user
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/admin/users/{$this->customer->id}")
            ->assertStatus(204);

        // Detail must no longer exist and cache must be gone
        $detailKey = md5(User::where('id', $this->customer->id)->with(['shop', 'addresses'])->toRawSql());
        $this->assertFalse(
            Cache::tags([self::RESOURCE])->has($detailKey)
        );
    }

    public function test_listing_cache_is_flushed_after_user_mutation(): void
    {
        // Warm listing cache
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/admin/users')
            ->assertHeader('X-Cache-Status', 'MISS');

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/admin/users')
            ->assertHeader('X-Cache-Status', 'HIT');

        // Deactivate triggers flush
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/admin/users/{$this->customer->id}/deactivate")
            ->assertStatus(204);

        // Listing must be cold again
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/admin/users')
            ->assertStatus(200)
            ->assertHeader('X-Cache-Status', 'MISS');
    }
}
