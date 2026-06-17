<?php

namespace App\Exceptions;

/**
 * Thrown when JWT authentication or parsing fails.
 *
 * Mapped to custom error codes like TOKEN_COULD_NOT_VERIFIED or TOKEN_COULD_NOT_PARSE.
 */
class OAuthException extends UnexpectedErrorException
{
    private string $errorCode;
    private int $statusCode;

    public function __construct(string $message = '', string $code = 'token_could_not_verified', int $statusCode = 401)
    {
        $this->errorCode = strtoupper($code);
        $this->statusCode = $statusCode;

        parent::__construct($message ?: $this->getDefaultMessage($code));
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    private function getDefaultMessage(string $code): string
    {
        return match ($code) {
            'token_could_not_verified' => 'The provided token is invalid, has expired, or has been blacklisted.',
            'token_could_not_parse'    => 'The token could not be parsed.',
            default                    => 'An authentication error occurred.',
        };
    }
}
