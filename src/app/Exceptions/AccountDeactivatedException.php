<?php

namespace App\Exceptions;

/**
 * Thrown by EnsureUserIsActive middleware when a deactivated user makes a request.
 *
 * HTTP 403 — rendered as:
 * { "error_code": "ACCOUNT_DEACTIVATED", "message": "Your account has been deactivated. Please contact support." }
 */
class AccountDeactivatedException extends UnexpectedErrorException
{
    public function __construct()
    {
        parent::__construct('Your account has been deactivated. Please contact support.');
    }

    public function getStatusCode(): int
    {
        return 403;
    }

    public function getErrorCode(): string
    {
        return 'ACCOUNT_DEACTIVATED';
    }
}
