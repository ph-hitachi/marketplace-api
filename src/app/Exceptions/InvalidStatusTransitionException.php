<?php

namespace App\Exceptions;

/**
 * Thrown when a seller attempts an illegal order status transition.
 *
 * HTTP 422 — rendered as:
 * { "error_code": "INVALID_STATUS_TRANSITION", "message": "Invalid status transition from X to Y." }
 */
class InvalidStatusTransitionException extends UnexpectedErrorException
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
