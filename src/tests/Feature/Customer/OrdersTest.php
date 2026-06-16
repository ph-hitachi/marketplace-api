<?php

namespace Tests\Feature\Customer;

use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Enums\CancelReason;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdersTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $seller1;
    private User $seller2;
    private Product $product1;
    private Product $product2;
    private Address $address;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->token    = $this->customer->createToken('token')->plainTextToken;

        $this->seller1 = User::factory()->create(['role' => 'seller']);
        $this->seller2 = User::factory()->create(['role' => 'seller']);

        $this->product1 = Product::create([
            'seller_id' => $this->seller1->id,
            'name'      => 'Product A',
            'price'     => 100.00,
            'stock'     => 10,
            'is_active' => true,
        ]);

        $this->product2 = Product::create([
            'seller_id' => $this->seller2->id,
            'name'      => 'Product B',
            'price'     => 150.00,
            'stock'     => 5,
            'is_active' => true,
        ]);

        $this->address = Address::create([
            'user_id'       => $this->customer->id,
            'label'         => 'Home',
            'address_line1' => 'Rizal St',
            'city'          => 'Makati',
            'province'      => 'Metro Manila',
            'postal_code'   => '1200',
            'country'       => 'Philippines',
        ]);
    }

    public function test_place_order_via_wallet_success(): void
    {
        $wallet = $this->customer->wallets()->first();
        $wallet->balance = 500.00;
        $wallet->save();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/customer/orders', [
                'address_id'     => $this->address->id,
                'payment_method' => 'wallet',
                'wallet_id'      => $wallet->id,
                'items' => [
                    ['product_id' => $this->product1->id, 'quantity' => 2]
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'orders']);

        // Verify stock decremented
        $this->assertEquals(8, $this->product1->fresh()->stock);
        $this->assertEquals(8, $this->product1->fresh()->stock);

        // Verify separate orders created per seller
        $this->assertDatabaseCount('orders', 1);
        $this->assertEquals(300.00, $wallet->fresh()->balance);

        // Verify seller balance is NOT yet credited (money onhold)
        $sellerWallet1 = $this->seller1->wallets()->where('is_default', true)->first();
        $this->assertEquals(0.00, $sellerWallet1->balance);

        // Verify ledger transactions created (status is 'on_hold')
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'type'      => 'purchase',
            'amount'    => 200.00,
            'status'    => 'pending',
        ]);

    }

    public function test_place_order_via_cod_success(): void
    {
        $wallet = $this->customer->wallets()->first();
        $wallet->balance = 500.00;
        $wallet->save();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/customer/orders', [
                'address_id'     => $this->address->id,
                'payment_method' => 'cod',
                'items' => [
                    ['product_id' => $this->product1->id, 'quantity' => 2]
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'orders']);

        // Verify stock decremented
        $this->assertEquals(8, $this->product1->fresh()->stock);

        // Verify wallet balance remained unchanged
        $this->assertEquals(500.00, $wallet->fresh()->balance);

        // Verify no transactions logged
        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    public function test_place_order_invalid_payment_method_validation_failure(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/customer/orders', [
                'address_id'     => $this->address->id,
                'payment_method' => 'card', // Invalid
                'items' => [
                    ['product_id' => $this->product1->id, 'quantity' => 1]
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }

    public function test_place_order_missing_wallet_id_validation_failure(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/customer/orders', [
                'address_id'     => $this->address->id,
                'payment_method' => 'wallet',
                // wallet_id is missing
                'items' => [
                    ['product_id' => $this->product1->id, 'quantity' => 1]
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['wallet_id']);
    }

    public function test_place_order_negative_quantity_validation_failure(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/customer/orders', [
                'address_id'     => $this->address->id,
                'payment_method' => 'cod',
                'items' => [
                    ['product_id' => $this->product1->id, 'quantity' => -5]
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.quantity']);
    }

    public function test_cannot_place_order_using_other_users_address_idor(): void
    {
        $otherUser = User::factory()->create(['role' => 'customer']);
        $otherAddress = Address::create([
            'user_id'       => $otherUser->id,
            'label'         => 'Home',
            'address_line1' => 'St',
            'city'          => 'City',
            'province'      => 'Province',
            'postal_code'   => '1234',
            'country'       => 'Philippines',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/customer/orders', [
                'address_id'     => $otherAddress->id,
                'payment_method' => 'cod',
                'items' => [
                    ['product_id' => $this->product1->id, 'quantity' => 1]
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['address_id']);
    }

    public function test_cannot_place_order_using_other_users_wallet_idor(): void
    {
        $otherUser = User::factory()->create(['role' => 'customer']);
        $otherWallet = $otherUser->wallets()->first();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/customer/orders', [
                'address_id'     => $this->address->id,
                'payment_method' => 'wallet',
                'wallet_id'      => $otherWallet->id,
                'items' => [
                    ['product_id' => $this->product1->id, 'quantity' => 1]
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['wallet_id']);
    }

    public function test_insufficient_wallet_balance_failure(): void
    {
        $wallet = $this->customer->wallets()->first();
        $wallet->balance = 50.00;
        $wallet->save();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/customer/orders', [
                'address_id'     => $this->address->id,
                'payment_method' => 'wallet',
                'wallet_id'      => $wallet->id,
                'items' => [
                    ['product_id' => $this->product1->id, 'quantity' => 1]
                ],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'error_code' => 'INSUFFICIENT_BALANCE',
            ]);
    }

    public function test_cannot_place_order_for_deactivated_product(): void
    {
        // Deactivate the product
        $this->product1->update(['is_active' => false]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/customer/orders', [
                'address_id'     => $this->address->id,
                'payment_method' => 'cod',
                'items' => [
                    ['product_id' => $this->product1->id, 'quantity' => 1]
                ],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'error_code' => 'PRODUCT_UNAVAILABLE',
            ]);
    }

    public function test_cancel_pending_wallet_order_refunds_correctly(): void
    {
        $wallet = $this->customer->wallets()->first();
        $wallet->balance = 500.00;
        $wallet->save();

        $placeResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/customer/orders', [
                'address_id'     => $this->address->id,
                'payment_method' => 'wallet',
                'wallet_id'      => $wallet->id,
                'items' => [
                    ['product_id' => $this->product1->id, 'quantity' => 2]
                ],
            ]);

        $orderId = $placeResponse->json('orders.0.id');
        $order = Order::find($orderId);

        // Cancel order with reason 1 (Mind Change)
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/customer/orders/{$order->id}/cancel", [
                'cancel_reason' => 1,
            ]);

        $response->assertStatus(200);

        // Verify stock restored
        $this->assertEquals(10, $this->product1->fresh()->stock);

        // Verify balance refunded
        $this->assertEquals(500.00, $wallet->fresh()->balance);

        // Verify cancel status and fields
        $freshOrder = $order->fresh();
        $this->assertEquals('cancelled', $freshOrder->status);
        $this->assertEquals(CancelReason::CUSTOMER_CHANGE_OF_MIND, $freshOrder->cancel_reason);
        $this->assertNotNull($freshOrder->cancel_at);

        // Verify original transaction is marked refund
        $originalTx = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', 'purchase')
            ->where('reference_id', $order->id)
            ->first();
        $this->assertEquals('cancelled', $originalTx->status);

        // Verify refund log in transactions
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'type'      => 'refund',
            'amount'    => 200.00,
            'status'    => 'completed',
        ]);
    }

    public function test_cancel_reason_validation_rules(): void
    {
        $wallet = $this->customer->wallets()->first();
        $wallet->balance = 500.00;
        $wallet->save();

        $placeResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/customer/orders', [
                'address_id'     => $this->address->id,
                'payment_method' => 'wallet',
                'wallet_id'      => $wallet->id,
                'items' => [
                    ['product_id' => $this->product1->id, 'quantity' => 2]
                ],
            ]);

        $orderId = $placeResponse->json('orders.0.id');
        $order = Order::find($orderId);

        // Missing reason fails validation
        $response1 = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/customer/orders/{$order->id}/cancel", []);
        $response1->assertStatus(422)
            ->assertJsonValidationErrors(['cancel_reason']);

        // Reason 5 (Other) requires notes
        $response2 = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/customer/orders/{$order->id}/cancel", [
                'cancel_reason' => 5,
            ]);
        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['cancel_reason_notes']);

        // Reason 5 (Other) with notes succeeds
        $response3 = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/customer/orders/{$order->id}/cancel", [
                'cancel_reason' => 5,
                'cancel_reason_notes' => 'Some specific notes',
            ]);
        $response3->assertStatus(200);
        $this->assertEquals('Some specific notes', $order->fresh()->cancel_reason_notes);
    }

    public function test_cancel_delivered_order_refunds_correctly(): void
    {
        $wallet = $this->customer->wallets()->first();
        $wallet->balance = 500.00;
        $wallet->save();

        $placeResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/customer/orders', [
                'address_id'     => $this->address->id,
                'payment_method' => 'wallet',
                'wallet_id'      => $wallet->id,
                'items' => [
                    ['product_id' => $this->product1->id, 'quantity' => 2]
                ],
            ]);

        $orderId = $placeResponse->json('orders.0.id');
        $order = Order::find($orderId);

        // Advance order to delivered
        $order->status = 'delivered';
        $order->save();

        // Cancel delivered order
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/customer/orders/{$order->id}/cancel", [
                'cancel_reason' => 5,
                'cancel_reason_notes' => 'Defective product',
            ]);

        $response->assertStatus(200);

        // Verify stock restored
        $this->assertEquals(10, $this->product1->fresh()->stock);

        // Verify balance refunded
        $this->assertEquals(500.00, $wallet->fresh()->balance);

        // Seller was never confirmed, so seller wallet is 0
        $sellerWallet1 = $this->seller1->wallets()->where('is_default', true)->first();
        $this->assertEquals(0.00, $sellerWallet1->balance);
    }

    public function test_cancel_blocked_when_order_in_transit(): void
    {
        $wallet = $this->customer->wallets()->first();
        $wallet->balance = 500.00;
        $wallet->save();

        $placeResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/customer/orders', [
                'address_id'     => $this->address->id,
                'payment_method' => 'wallet',
                'wallet_id'      => $wallet->id,
                'items' => [
                    ['product_id' => $this->product1->id, 'quantity' => 2]
                ],
            ]);

        $orderId = $placeResponse->json('orders.0.id');
        $order = Order::find($orderId);

        // Move to processing
        $order->status = 'shipped';
        $order->save();

        // Attempt cancel
        $response1 = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/customer/orders/{$order->id}/cancel", ['cancel_reason' => 1]);
        $response1->assertStatus(422)
            ->assertJson(['error_code' => 'ORDER_IN_TRANSIT']);

        // Move to shipped
        $order->status = 'shipped';
        $order->save();

        // Attempt cancel
        $response2 = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/customer/orders/{$order->id}/cancel", ['cancel_reason' => 1]);
        $response2->assertStatus(422)
            ->assertJson(['error_code' => 'ORDER_IN_TRANSIT']);
    }

    public function test_confirm_releases_funds_to_seller_successfully(): void
    {
        $wallet = $this->customer->wallets()->first();
        $wallet->balance = 500.00;
        $wallet->save();

        $placeResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/customer/orders', [
                'address_id'     => $this->address->id,
                'payment_method' => 'wallet',
                'wallet_id'      => $wallet->id,
                'items' => [
                    ['product_id' => $this->product1->id, 'quantity' => 2]
                ],
            ]);

        $orderId = $placeResponse->json('orders.0.id');
        $order = Order::find($orderId);

        $order->status = 'delivered';
        $order->save();

        // Confirm order
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/customer/orders/{$order->id}/confirm");

        $response->assertStatus(200);

        // Verify status and timestamps
        $freshOrder = $order->fresh();
        $this->assertEquals('confirmed', $freshOrder->status);
        $this->assertNotNull($freshOrder->completed_at);

        // Verify customer purchase transaction is marked 'purchase'
        $custTx = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', 'purchase')
            ->where('reference_id', $order->id)
            ->first();
        $this->assertEquals('completed', $custTx->status);

        // Verify seller default wallet credited
        $sellerWallet = $this->seller1->wallets()->where('is_default', true)->first();
        $this->assertEquals(200.00, $sellerWallet->fresh()->balance);

        // Verify sales transaction created
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id'      => $sellerWallet->id,
            'type'           => 'sales',
            'amount'         => 200.00,
            'status'         => 'completed',
            'reference_id'   => $order->id,
        ]);
    }

    public function test_cannot_confirm_already_confirmed_or_cancelled_order(): void
    {
        $wallet = $this->customer->wallets()->first();
        $wallet->balance = 500.00;
        $wallet->save();

        $placeResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/customer/orders', [
                'address_id'     => $this->address->id,
                'payment_method' => 'wallet',
                'wallet_id'      => $wallet->id,
                'items' => [
                    ['product_id' => $this->product1->id, 'quantity' => 1]
                ],
            ]);

        $orderId = $placeResponse->json('orders.0.id');
        $order = Order::find($orderId);

        // Cancel the order first
        $order->status = 'cancelled';
        $order->save();

        // Attempt confirm on cancelled order
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/customer/orders/{$order->id}/confirm");

        $response->assertStatus(422)
            ->assertJson([
                'error_code' => 'INVALID_STATUS_TRANSITION',
            ]);

        // Attempt confirm on confirmed order
        $order->status = 'confirmed';
        $order->save();

        $response2 = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/customer/orders/{$order->id}/confirm");

        $response2->assertStatus(422)
            ->assertJson([
                'error_code' => 'INVALID_STATUS_TRANSITION',
            ]);
    }
}

