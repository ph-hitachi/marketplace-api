<?php

namespace App\Exceptions;

/**
 * Thrown when a user's wallet balance is insufficient to cover an order.
 *
 * HTTP 422 — rendered as:
 * { "error_code": "INSUFFICIENT_BALANCE", "message": "..." }
 */
class InsufficientBalanceException extends UnexpectedErrorException
{
    public function __construct(string $message = 'Insufficient wallet balance.')
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return 422;
    }

    public function getErrorCode(): string
    {
        return 'INSUFFICIENT_BALANCE';
    }
}
