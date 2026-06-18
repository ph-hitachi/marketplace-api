<?php
namespace Database\Factories;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
class OrderFactory extends Factory {
    protected $model = Order::class;
    public function definition() {
        return [
            'customer_id' => \App\Models\User::factory(),
            'shop_id' => \App\Models\Shop::factory(),
            'address_id' => \App\Models\Address::factory(),
            'wallet_id' => \App\Models\Wallet::factory(),
            'payment_method' => 'wallet',
            'status' => 'pending',
            'batch_ref' => fake()->uuid(),
            'total_amount' => 1500.50,
            'cancel_reason' => null,
            'cancel_reason_notes' => null,
            'shipped_at' => null,
            'delivered_at' => null,
            'cancel_at' => null,
            'completed_at' => null,
        ];
    }
}