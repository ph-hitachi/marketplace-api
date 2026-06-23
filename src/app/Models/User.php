<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

use App\Support\Cacheable;

#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, Cacheable;

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::created(function (User $user) {
            $user->wallets()->create([
                'label'      => 'Default',
                'balance'    => 0.00,
                'is_default' => true,
            ]);
        });
    }

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    // ── Relations ──────────────────────────────────────────────

    public function shop(): HasOne
    {
        return $this->hasOne(Shop::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /** Products this user sells (seller role). */
    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(Product::class, Shop::class);
    }

    /** Orders placed by this user (customer role). */
    public function customerOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    /** Orders assigned to this user's shop (seller role). */
    public function sellerOrders(): HasManyThrough
    {
        return $this->hasManyThrough(Order::class, Shop::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function walletTransactions(): HasManyThrough
    {
        return $this->hasManyThrough(WalletTransaction::class, Wallet::class);
    }

    // ── Helpers ─────────────────────────────────────────────────

    public function isSeller(): bool
    {
        return $this->role === 'seller';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function toArray()
    {
        $array = parent::toArray();

        $user = auth()->user();
        
        if (!$user || ($user->id !== $this->id && $user->role !== 'admin')) {
            if (array_key_exists('email', $array)) {
                $array['email'] = null;
            }
        }

        return $array;
    }
}
