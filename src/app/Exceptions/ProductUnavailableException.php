<?php

namespace App\Exceptions;

/**
 * Thrown when a requested product is not found or not available for ordering.
 *
 * HTTP 422 — rendered as:
 * { "error_code": "PRODUCT_UNAVAILABLE", "message": "Product ID X is not available." }
 */
class ProductUnavailableException extends UnexpectedErrorException
{
    public function __construct(int $productId)
    {
        parent::__construct("Product ID {$productId} is not available.");
    }

    public function getStatusCode(): int
    {
        return 422;
    }

    public function getErrorCode(): string
    {
        return 'PRODUCT_UNAVAILABLE';
    }
}
