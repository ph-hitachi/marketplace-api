<?php
namespace Database\Factories;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
class WalletTransactionFactory extends Factory {
    protected $model = WalletTransaction::class;
    public function definition() {
        return [
            'wallet_id' => 1,
            'type' => 'topup',
            'amount' => 1000.00,
            'balance_after' => 6000.00,
            'reference_id' => null,
            'status' => 'completed',
            'description' => 'Wallet top-up via Bank Transfer',
        ];
    }
}