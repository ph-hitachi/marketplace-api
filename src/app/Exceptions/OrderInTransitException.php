<?php

namespace App\Exceptions;

/**
 * Attempting to cancel an order that has already been shipped.
 * @message Cannot cancel the order while it is shipped.
 */
class OrderInTransitException extends ServerErrorException
{
    public function __construct(string $message = 'Cannot cancel the order while it is shipped.')
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return 409;
    }

    public function getErrorCode(): string
    {
        return 'ORDER_IN_TRANSIT';
    }
}
