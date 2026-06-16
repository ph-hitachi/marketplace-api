<?php

namespace App\Policies;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CartPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'customer';
    }

    public function view(User $user, Cart $cart): bool
    {
        return $user->id === $cart->customer_id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'customer';
    }

    public function update(User $user, Cart $cart): bool
    {
        return false;
    }

    public function delete(User $user, Cart $cart): bool
    {
        return $user->id === $cart->customer_id;
    }

    public function restore(User $user, Cart $cart): bool
    {
        return false;
    }

    public function forceDelete(User $user, Cart $cart): bool
    {
        return false;
    }
}
