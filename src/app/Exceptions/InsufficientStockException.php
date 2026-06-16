<?php

namespace App\Exceptions;

/**
 * Thrown when a requested product quantity exceeds available stock.
 *
 * HTTP 422 — rendered as:
 * { "error_code": "INSUFFICIENT_STOCK", "message": "Insufficient stock for product \"X\". Available: N." }
 */
class InsufficientStockException extends UnexpectedErrorException
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
