<?php

namespace App\Exceptions;

/**
 * Attempting to move an order to an illogical state (e.g., pending to delivered).
 * @message Invalid status transition from "X" to "Y".
 */
class InvalidStatusTransitionException extends ServerErrorException
{
    public function __construct(string $from, string $to)
    {
        parent::__construct("Invalid status transition from \"{$from}\" to \"{$to}\".");
    }

    public function getStatusCode(): int
    {
        return 409;
    }

    public function getErrorCode(): string
    {
        return 'INVALID_STATUS_TRANSITION';
    }
}
