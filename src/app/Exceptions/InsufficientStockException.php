<?php

namespace App\Exceptions;

/**
 * Placing an order for a quantity that exceeds available inventory.
 * @message Insufficient stock for product "X". Available: N.
 */
class InsufficientStockException extends ServerErrorException
{
    public function __construct(string $productName, int $available)
    {
        parent::__construct(
            "Insufficient stock for product \"{$productName}\". Available: {$available}."
        );
    }

    public function getStatusCode(): int
    {
        return 422;
    }

    public function getErrorCode(): string
    {
        return 'INSUFFICIENT_STOCK';
    }
}
