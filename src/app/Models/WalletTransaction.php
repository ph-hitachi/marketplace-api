<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Wallet;

class WalletTransaction extends Model
{
    use HasFactory;

    // No updated_at — this is an immutable ledger
    const UPDATED_AT = null;

    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'reference_id',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (WalletTransaction $transaction) {
            $wallet = $transaction->wallet()->lockForUpdate()->first();

            $transaction->balance_before = $wallet->balance;
            $amount = $transaction->amount;

            if ($transaction->type === 'purchase') {
                $transaction->balance_after = (float) $transaction->balance_before - $amount;
            }

            if (in_array($transaction->type, ['sales', 'refund', 'topup'])) {
                $transaction->balance_after = (float) $transaction->balance_before + $amount;
            }

            if ($transaction->type === 'purchase' && $transaction->balance_after < 0) {
                throw new \App\Exceptions\InsufficientBalanceException();
            }

            $wallet->balance = $transaction->balance_after;
            $wallet->save();
        });
    }

    // ── Relations ──────────────────────────────────────────────

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /** The related order (for deduction / refund types). */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'reference_id');
    }
}
