<?php

namespace App\Exceptions;

/**
 * Thrown when login authentication fails due to invalid credentials.
 *
 * HTTP 401 — rendered as:
 * { "error_code": "UNAUTHENTICATED", "message": "Invalid credentials." }
 */
class InvalidCredentialsException extends UnexpectedErrorException
{
    public function __construct()
    {
        parent::__construct('The email or password you entered is incorrect.');
    }

    public function getStatusCode(): int
    {
        return 401;
    }

    public function getErrorCode(): string
    {
        return 'INVALID_CREDENTIALS';
    }
}
