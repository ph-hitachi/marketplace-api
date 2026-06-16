<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Base exception for all marketplace domain errors.
 * All subclasses are registered in bootstrap/app.php and
 * rendered as consistent JSON responses.
 */
class UnexpectedErrorException extends RuntimeException
{
    public function getStatusCode(): int
    {
        return 500;
    }

    public function getErrorCode(): string
    {
        return 'SERVER_ERROR';
    }
}
