<?php

namespace App\Exceptions;

class OrderInTransitException extends UnexpectedErrorException
{
    public function __construct(string $message = 'Cannot cancel the order while it is in processing or shipped.')
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
