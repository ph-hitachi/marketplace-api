<?php

namespace App\Exceptions;

/**
 * Attempting to delete a user that has active orders tied to it.
 * @message Cannot delete user with active or non-cancelled orders.
 */
class UserDeleteBlockedException extends ServerErrorException
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
