<?php

namespace Tests\Feature\Seller;

use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductsTest extends TestCase
{
    use RefreshDatabase;

    private User $seller1;
    private User $seller2;
    private string $token1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller1 = User::factory()->create(['role' => 'seller']);
        $this->token1  = auth('api')->login($this->seller1);

        $this->seller2 = User::factory()->create(['role' => 'seller']);
    }

    public function test_seller_can_list_own_products(): void
    {
        Product::create([
            'seller_id' => $this->seller1->id,
            'name'      => 'Seller 1 Product',
            'price'     => 10,
            'stock'     => 5,
        ]);

        Product::create([
            'seller_id' => $this->seller2->id,
            'name'      => 'Seller 2 Product',
            'price'     => 20,
            'stock'     => 1,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson('/api/seller/products');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Seller 1 Product'])
            ->assertJsonMissing(['name' => 'Seller 2 Product']);
    }



    public function test_seller_cannot_view_or_update_other_sellers_product(): void
    {
        $product2 = Product::create([
            'seller_id' => $this->seller2->id,
            'name'      => 'Seller 2 Product',
            'price'     => 10,
            'stock'     => 5,
        ]);

        // Get own check
        $responseGet = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson("/api/seller/products/{$product2->id}");
        $responseGet->assertStatus(403);

        // Put own check
        $responsePut = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->putJson("/api/seller/products/{$product2->id}", [
                'price' => 12.00,
            ]);
        $responsePut->assertStatus(403);
    }

    public function test_seller_can_delete_own_product(): void
    {
        $product = Product::create([
            'seller_id' => $this->seller1->id,
            'name'      => 'To Delete',
            'price'     => 10,
            'stock'     => 5,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->deleteJson("/api/seller/products/{$product->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_seller_cannot_delete_product_with_any_orders(): void
    {
        $product = Product::create([
            'seller_id' => $this->seller1->id,
            'name'      => 'Active Product',
            'price'     => 10,
            'stock'     => 5,
        ]);

        $customer = User::factory()->create(['role' => 'customer']);
        $address  = Address::create([
            'user_id'       => $customer->id,
            'label'         => 'Home',
            'address_line1' => 'St',
            'city'          => 'City',
            'province'      => 'Province',
            'postal_code'   => '1234',
            'country'       => 'Philippines',
        ]);

        // Create an order in any status (e.g. delivered)
        $order = Order::create([
            'customer_id'    => $customer->id,
            'seller_id'      => $this->seller1->id,
            'address_id'     => $address->id,
            'payment_method' => 'cod',
            'status'         => 'delivered',
            'total_amount'   => 10.00,
        ]);
        $order->items()->create([
            'product_id'     => $product->id,
            'product_name'   => $product->name,
            'unit_price'     => $product->price,
            'quantity'       => 1,
            'subtotal'       => 10.00,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->deleteJson("/api/seller/products/{$product->id}");

        $response->assertStatus(403); // Policy block or auth block due to active order dependency
        $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
    }

    public function test_seller_can_activate_and_deactivate_own_product(): void
    {
        $product = Product::create([
            'seller_id' => $this->seller1->id,
            'name'      => 'Toggle Product',
            'price'     => 10,
            'stock'     => 5,
        ]);

        // Deactivate
        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->patchJson("/api/seller/products/{$product->id}/deactivate");

        $response->assertStatus(204);
            
        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => false]);

        // Activate
        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->patchJson("/api/seller/products/{$product->id}/activate");

        $response->assertStatus(204);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => true]);
    }
}
