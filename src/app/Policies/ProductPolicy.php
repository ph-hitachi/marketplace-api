<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Seller can view their own product (even if inactive/soft-deleted).
     */
    public function view(User $user, Product $product): bool
    {
        return $user->id === $product->shop->user_id;
    }

    /**
     * Seller can update their own product.
     */
    public function update(User $user, Product $product): bool
    {
        return $user->id === $product->shop->user_id;
    }

    /**
     * Seller can delete their own product only if it has never been ordered.
     */
    public function delete(User $user, Product $product): bool
    {
        if ($user->id !== $product->shop->user_id) {
            return false;
        }

        $hasOrders = $product->orders()->exists();

        return ! $hasOrders;
    }
}
