<?php

namespace App\Exceptions;

/**
 * Thrown when an admin tries to delete a user who has active or non-cancelled orders.
 *
 * HTTP 422 — rendered as:
 * { "error_code": "DELETE_BLOCKED", "message": "Cannot delete user with active or non-cancelled orders." }
 */
class UserDeleteBlockedException extends UnexpectedErrorException
{
    public function __construct()
    {
        parent::__construct('Cannot delete user with active or non-cancelled orders.');
    }

    public function getStatusCode(): int
    {
        return 409;
    }

    public function getErrorCode(): string
    {
        return 'DELETE_BLOCKED';
    }
}
