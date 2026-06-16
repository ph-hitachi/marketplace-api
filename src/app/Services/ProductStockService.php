<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;

class ProductStockService
{
    /**
     * Deduct stock from a product.
     *
     * @throws InsufficientStockException
     */
    public function deduct(Product $product, int $quantity): void
    {
        if ($product->stock < $quantity) {
            throw new InsufficientStockException(
                $product->name,
                $product->stock
            );
        }

        $product->decrement('stock', $quantity);
    }

    /**
     * Restore stock to a product.
     */
    public function restore(Product $product, int $quantity): void
    {
        $product->increment('stock', $quantity);
    }
}
