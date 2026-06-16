<?php

namespace App\Services;

use App\Models\Order;

class OrderPaymentService
{
    /**
     * Deduct funds from the customer's wallet and place them on hold (pending).
     */
    public function pay(Order $order): void
    {
        if ($order->payment_method === 'wallet' && $order->wallet_id) {
            $order->wallet->transactions()->create([
                'type' => 'purchase',
                'amount' => (float) $order->total_amount,
                'reference_id' => $order->id,
                'status' => 'pending',
                'description' => "Payment for order #{$order->id}",
            ]);
        }
    }

    /**
     * Refund a cancelled order back to the customer's wallet.
     */
    public function refund(Order $order): void
    {
        if ($order->payment_method === 'wallet' && $order->wallet_id) {
            // cancel previously on-hold transaction
            $order->wallet->transactions()
                ->where('type', 'purchase')
                ->where('reference_id', $order->id)
                ->update(['status' => 'cancelled']);

            // create new transaction for refund
            $order->wallet->transactions()->create([
                'type' => 'refund',
                'amount' => (float) $order->total_amount,
                'reference_id' => $order->id,
                'status' => 'completed',
                'description' => "Refund for cancelled order #{$order->id}",
            ]);
        }
    }

    /**
     * Release held funds to the seller's wallet after order confirmation.
     */
    public function release(Order $order): void
    {
        if ($order->payment_method === 'wallet' && $order->wallet_id) {
            $order->wallet->transactions()
                ->where('type', 'purchase')
                ->where('reference_id', $order->id)
                ->update(['status' => 'completed']);

            $sellerWallet = $order->seller->wallets()->where('is_default', true)->first();

            if ($sellerWallet) {
                $sellerWallet->transactions()->create([
                    'type' => 'sales',
                    'amount' => (float) $order->total_amount,
                    'reference_id' => $order->id,
                    'status' => 'completed',
                    'description' => "Sales release for order #{$order->id}",
                ]);
            }
        }
    }
}
