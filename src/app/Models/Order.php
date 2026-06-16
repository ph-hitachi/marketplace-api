<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Enums\CancelReason;

/**
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrderItem> $items
 */
class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'seller_id',
        'address_id',
        'wallet_id',
        'payment_method',
        'status',
        'batch_ref',
        'total_amount',
        'cancel_reason',
        'cancel_reason_notes',
        'shipped_at',
        'delivered_at',
        'cancel_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'cancel_reason' => CancelReason::class,
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancel_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // ── Relations ──────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

}
