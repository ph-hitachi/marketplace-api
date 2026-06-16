<?php
namespace Database\Factories;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;
class WalletFactory extends Factory {
    protected $model = Wallet::class;
    public function definition() {
        return [
            'user_id' => 1,
            'label' => 'My Personal Wallet',
            'balance' => 5000.00,
            'is_default' => true,
            'is_active' => true,
        ];
    }
}