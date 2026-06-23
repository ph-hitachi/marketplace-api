<?php

namespace App\Exceptions;

/**
 * Placing an order when the wallet balance is too low.
 * @message Insufficient wallet balance.
 */
class InsufficientBalanceException extends ServerErrorException
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
