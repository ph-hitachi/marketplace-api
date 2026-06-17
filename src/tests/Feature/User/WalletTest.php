<?php

namespace Tests\Feature\User;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_list_wallets(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $token = auth('api')->login($customer);

        // Default wallet already auto-created with is_default = true
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user/wallets');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'label' => 'Default',
                'is_default' => true
            ]);
    }

    public function test_customer_can_create_wallet_with_mass_assignment_protection(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $token = auth('api')->login($customer);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/user/wallets', [
                'label'   => 'Savings Wallet',
                'balance' => 99999.00, // Should be ignored
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('wallet.label', 'Savings Wallet')
            ->assertJsonPath('wallet.balance', '0.00')
            ->assertJsonPath('wallet.is_default', false);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $customer->id,
            'label'   => 'Savings Wallet',
            'balance' => 0.00,
            'is_default' => false,
        ]);
    }

    public function test_customer_can_set_default_wallet(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $token = auth('api')->login($customer);

        $defaultWallet = $customer->wallets()->where('is_default', true)->first();

        // Create a new wallet
        $newWallet = $customer->wallets()->create([
            'label'      => 'Savings',
            'is_default' => false,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/user/wallets/{$newWallet->id}/default");

        $response->assertStatus(200)
            ->assertJsonPath('wallet.is_default', true);

        // Verify default wallet changed
        $this->assertFalse((bool) $defaultWallet->fresh()->is_default);
        $this->assertTrue((bool) $newWallet->fresh()->is_default);
    }

    public function test_customer_can_topup_own_wallet(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $token = auth('api')->login($customer);
        $wallet   = $customer->wallets()->first();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/user/wallets/{$wallet->id}/topup", [
                'amount' => 1000.00,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('balance', '1000.00');

        $this->assertDatabaseHas('wallets', [
            'id'      => $wallet->id,
            'balance' => 1000.00,
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id'      => $wallet->id,
            'type'           => 'topup',
            'amount'         => 1000.00,
            'balance_before' => 0.00,
            'balance_after'  => 1000.00,
            'status'         => 'completed',
        ]);
    }

    public function test_customer_cannot_topup_other_users_wallet_idor(): void
    {
        $customer1 = User::factory()->create(['role' => 'customer']);
        $customer2 = User::factory()->create(['role' => 'customer']);
        $token1 = auth('api')->login($customer1);
        $wallet2   = $customer2->wallets()->first();

        $response = $this->withHeader('Authorization', "Bearer {$token1}")
            ->postJson("/api/user/wallets/{$wallet2->id}/topup", [
                'amount' => 500.00,
            ]);

        $response->assertStatus(403);
    }

    public function test_seller_can_list_and_manage_wallets(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $token = auth('api')->login($seller);

        // List
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user/wallets');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'label' => 'Default',
                'is_default' => true
            ]);

        // Create
        $response2 = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/user/wallets', ['label' => 'Sales Wallet']);

        $response2->assertStatus(201)
            ->assertJsonPath('wallet.label', 'Sales Wallet')
            ->assertJsonPath('wallet.is_default', false);

        $newWalletId = $response2->json('wallet.id');

        // Set Default
        $response3 = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/user/wallets/{$newWalletId}/default");

        $response3->assertStatus(200);
        $this->assertTrue((bool) Wallet::find($newWalletId)->is_default);
    }
}
