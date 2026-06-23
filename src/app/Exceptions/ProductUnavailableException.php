<?php

namespace App\Exceptions;

/**
 * Attempting to purchase a product that is inactive or deleted.
 * @message Product ID X is not available.
 */
class ProductUnavailableException extends ServerErrorException
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
