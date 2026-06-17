<?php

namespace Tests\Feature\Seller;

use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdersTest extends TestCase
{
    use RefreshDatabase;

    private User $seller1;
    private User $seller2;
    private User $customer;
    private string $token1;
    private Order $order1;
    private Order $order2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller1  = User::factory()->create(['role' => 'seller']);
        $this->token1   = auth('api')->login($this->seller1);
        $this->seller2  = User::factory()->create(['role' => 'seller']);
        $this->customer = User::factory()->create(['role' => 'customer']);

        $address = Address::create([
            'user_id'       => $this->customer->id,
            'label'         => 'Home',
            'address_line1' => 'St',
            'city'          => 'City',
            'province'      => 'Province',
            'postal_code'   => '1234',
            'country'       => 'Philippines',
        ]);

        $product1 = Product::create(['seller_id' => $this->seller1->id, 'name' => 'Test 1', 'price' => 100, 'stock' => 10]);
        $product2 = Product::create(['seller_id' => $this->seller2->id, 'name' => 'Test 2', 'price' => 200, 'stock' => 10]);

        $this->order1 = Order::create([
            'customer_id'    => $this->customer->id,
            'seller_id'      => $this->seller1->id,
            'address_id'     => $address->id,
            'payment_method' => 'cod',
            'status'         => 'pending',
            'total_amount'   => 100.00,
        ]);
        $this->order1->items()->create([
            'product_id'     => $product1->id,
            'product_name'   => $product1->name,
            'unit_price'     => $product1->price,
            'quantity'       => 1,
            'subtotal'       => 100.00,
        ]);

        $this->order2 = Order::create([
            'customer_id'    => $this->customer->id,
            'seller_id'      => $this->seller2->id,
            'address_id'     => $address->id,
            'payment_method' => 'cod',
            'status'         => 'pending',
            'total_amount'   => 200.00,
        ]);
        $this->order2->items()->create([
            'product_id'     => $product2->id,
            'product_name'   => $product2->name,
            'unit_price'     => $product2->price,
            'quantity'       => 1,
            'subtotal'       => 200.00,
        ]);
    }

    public function test_seller_can_list_own_orders_only(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson('/api/seller/orders');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['total_amount' => '100.00'])
            ->assertJsonMissing(['total_amount' => '200.00']);
    }

    public function test_seller_cannot_view_or_update_other_sellers_orders(): void
    {
        $responseGet = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson("/api/seller/orders/{$this->order2->id}");
        $responseGet->assertStatus(403);

        $responsePatch = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->patchJson("/api/seller/orders/{$this->order2->id}/status", [
                'status' => 'shipped',
            ]);
        $responsePatch->assertStatus(403);
    }

    public function test_seller_can_advance_order_status_step_by_step(): void
    {
        // 1. pending -> shipped
        $response1 = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->patchJson("/api/seller/orders/{$this->order1->id}/status", [
                'status' => 'shipped',
            ]);
        $response1->assertStatus(200);
        $this->assertEquals('shipped', $this->order1->fresh()->status);

        // 2. shipped -> delivered
        $response2 = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->patchJson("/api/seller/orders/{$this->order1->id}/status", [
                'status' => 'delivered',
            ]);
        $response2->assertStatus(200);
        $this->assertEquals('delivered', $this->order1->fresh()->status);
    }

    public function test_seller_cannot_move_status_backward_or_skip_invalid_steps(): void
    {
        $this->order1->update(['status' => 'delivered']);

        // Try delivered -> shipped (backward)
        $responseBackward = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->patchJson("/api/seller/orders/{$this->order1->id}/status", [
                'status' => 'shipped',
            ]);
        $responseBackward->assertStatus(409)
            ->assertJson([
                'error_code' => 'INVALID_STATUS_TRANSITION',
            ]);

        // Try shipped -> pending (backward)
        $responsePending = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->patchJson("/api/seller/orders/{$this->order1->id}/status", [
                'status' => 'pending',
            ]);
        $responsePending->assertStatus(409);
    }



    public function test_seller_can_cancel_order_via_cancel_endpoint(): void
    {
        $product = Product::create([
            'seller_id' => $this->seller1->id,
            'name'      => 'Test Pro 2',
            'slug'      => 'test-pro-2',
            'price'     => 50.00,
            'stock'     => 10,
        ]);

        $wallet = $this->customer->wallets()->where('is_default', true)->first();
        $wallet->balance = 100.00;
        $wallet->save();

        $order = Order::create([
            'customer_id'    => $this->customer->id,
            'seller_id'      => $this->seller1->id,
            'address_id'     => $this->order1->address_id,
            'payment_method' => 'wallet',
            'wallet_id'      => $wallet->id,
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
        $product->decrement('stock', 1);

        // Seller cancels via cancel endpoint
        $responseCancel = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->patchJson("/api/seller/orders/{$order->id}/cancel", [
                'cancel_reason'       => 5,
                'cancel_reason_notes' => 'Inventory issue',
            ]);

        $responseCancel->assertStatus(200);
        $fresh = $order->fresh();
        $this->assertEquals('cancelled', $fresh->status);
        $this->assertEquals(5, $fresh->cancel_reason->value);
        $this->assertEquals('Inventory issue', $fresh->cancel_reason_notes);

        // Assert stock restored (since creation automatically decremented it, cancelling returns it to start value)
        $this->assertEquals(10, $product->fresh()->stock);

        // Assert wallet refunded
        $this->assertEquals(150.00, (float) $wallet->fresh()->balance);
    }

    public function test_seller_cannot_cancel_shipped_delivered_or_confirmed_orders(): void
    {
        // 1. Shipped order cancellation -> should throw OrderInTransitException
        $this->order1->update(['status' => 'shipped']);
        $response1 = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->patchJson("/api/seller/orders/{$this->order1->id}/cancel", [
                'cancel_reason' => 4,
            ]);
        $response1->assertStatus(409)
            ->assertJson([
                'error_code' => 'ORDER_IN_TRANSIT',
            ]);

        // 2. Delivered order cancellation -> should throw InvalidStatusTransitionException
        $this->order1->update(['status' => 'delivered']);
        $response2 = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->patchJson("/api/seller/orders/{$this->order1->id}/cancel", [
                'cancel_reason' => 5,
                'cancel_reason_notes' => 'Some note',
            ]);
        $response2->assertStatus(409)
            ->assertJson([
                'error_code' => 'INVALID_STATUS_TRANSITION',
            ]);

        // 3. Confirmed order cancellation -> should throw InvalidStatusTransitionException
        $this->order1->update(['status' => 'confirmed']);
        $response3 = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->patchJson("/api/seller/orders/{$this->order1->id}/cancel", [
                'cancel_reason' => 1,
            ]);
        $response3->assertStatus(409)
            ->assertJson([
                'error_code' => 'INVALID_STATUS_TRANSITION',
            ]);
    }

    public function test_seller_cannot_confirm_order_directly(): void
    {
        $this->order1->update(['status' => 'pending']);
        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->patchJson("/api/seller/orders/{$this->order1->id}/status", [
                'status' => 'confirmed',
            ]);
        $response->assertStatus(403)
            ->assertJson([
                'error_code' => 'FORBIDDEN',
            ]);
    }

    public function test_seller_cannot_see_customer_email_in_order_response(): void
    {
        // 1. List endpoint
        $responseList = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson('/api/seller/orders');
        
        $responseList->assertStatus(200)
            ->assertJsonMissingPath('data.0.customer.email');
        
        $data = $responseList->json('data.0.customer');
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayNotHasKey('email', $data);

        // 2. Show endpoint
        $responseShow = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson("/api/seller/orders/{$this->order1->id}");

        $responseShow->assertStatus(200)
            ->assertJsonMissingPath('order.customer.email');

        $dataShow = $responseShow->json('order.customer');
        $this->assertArrayHasKey('id', $dataShow);
        $this->assertArrayHasKey('name', $dataShow);
        $this->assertArrayNotHasKey('email', $dataShow);
    }
}
