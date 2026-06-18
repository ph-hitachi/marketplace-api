<?php

namespace Tests\Feature\Public;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_shops_publicly(): void
    {
        $seller1 = User::factory()->create(['role' => 'seller']);
        $shop1 = Shop::factory()->create([
            'user_id'   => $seller1->id,
            'shop_name' => 'Fabulous Garments',
        ]);

        $seller2 = User::factory()->create(['role' => 'seller']);
        $shop2 = Shop::factory()->create([
            'user_id'   => $seller2->id,
            'shop_name' => 'Vintage Artifacts',
        ]);

        $response = $this->getJson('/api/shops');

        $response->assertStatus(200)
            ->assertJsonStructure(['current_page', 'data'])
            ->assertJsonFragment(['shop_name' => 'Fabulous Garments'])
            ->assertJsonFragment(['shop_name' => 'Vintage Artifacts']);
    }

    public function test_can_view_specific_shop_profile_and_active_products(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $shop = Shop::factory()->create([
            'user_id'          => $seller->id,
            'shop_name'        => 'Custom Shop',
            'shop_description' => 'A wonderful marketplace.',
        ]);

        // Create active product
        $activeProduct = \App\Models\Product::create([
            'shop_id'   => $shop->id,
            'name'      => 'Active Item',
            'price'     => 100,
            'stock'     => 10,
            'is_active' => true,
        ]);

        // Create inactive product
        $inactiveProduct = \App\Models\Product::create([
            'shop_id'   => $shop->id,
            'name'      => 'Inactive Item',
            'price'     => 50,
            'stock'     => 5,
            'is_active' => false,
        ]);

        $response = $this->getJson("/api/shops/{$shop->id}");

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Custom Shop')
            ->assertJsonPath('description', 'A wonderful marketplace.')
            ->assertJsonStructure(['products'])
            ->assertJsonFragment(['name' => 'Active Item'])
            ->assertJsonMissing(['name' => 'Inactive Item']);
    }
}
