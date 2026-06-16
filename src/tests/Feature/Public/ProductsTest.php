<?php

namespace Tests\Feature\Public;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_products_only_shows_active(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);

        // Active product
        $active = Product::create([
            'seller_id' => $seller->id,
            'name'      => 'Active Product',
            'price'     => 100,
            'stock'     => 10,
            'is_active' => true,
        ]);

        // Inactive product
        $inactive = Product::create([
            'seller_id' => $seller->id,
            'name'      => 'Inactive Product',
            'price'     => 200,
            'stock'     => 10,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Active Product'])
            ->assertJsonMissing(['name' => 'Inactive Product']);
    }

    public function test_get_single_product_success(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $product = Product::create([
            'seller_id' => $seller->id,
            'name'      => 'Detailed Product',
            'price'     => 150,
            'stock'     => 5,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('product.name', 'Detailed Product');
    }

    public function test_get_inactive_product_returns_404(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $product = Product::create([
            'seller_id' => $seller->id,
            'name'      => 'Secret Product',
            'price'     => 150,
            'stock'     => 5,
            'is_active' => false,
        ]);

        $response = $this->getJson("/api/products/{$product->id}");
        $response->assertStatus(404);
    }
}
