<?php

namespace App\Docs;

use Dedoc\Scramble\Extensions\ExceptionToResponseExtension;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types as OpenApiTypes;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GlobalExceptionExtension extends ExceptionToResponseExtension
{
    public function shouldHandle(Type $type)
    {
        if (!$type instanceof ObjectType) {
            return false;
        }

        // Domain exceptions should be handled by UnexpectedErrorExceptionExtension
        if ($type->isInstanceOf(\App\Exceptions\UnexpectedErrorException::class)) {
            return false;
        }

        return $type->isInstanceOf(AuthenticationException::class) ||
               $type->isInstanceOf(AuthorizationException::class) ||
               $type->isInstanceOf(AccessDeniedHttpException::class) ||
               $type->isInstanceOf(ThrottleRequestsException::class) ||
               $type->isInstanceOf(NotFoundHttpException::class) ||
               $type->isInstanceOf(ModelNotFoundException::class) ||
               $type->isInstanceOf(\Throwable::class);
    }

    public function toResponse(Type $type)
    {
        $className = ltrim($type->name, '\\');
        $baseName = class_basename($className);

        $statusCode = 500;
        $errorCode = 'INTERNAL_ERROR';
        $message = 'Server Error';

        if ($type->isInstanceOf(AuthenticationException::class)) {
            $statusCode = 401;
            $errorCode = 'UNAUTHENTICATED';
            $message = 'Unauthenticated.';
        } elseif ($type->isInstanceOf(AuthorizationException::class) || $type->isInstanceOf(AccessDeniedHttpException::class)) {
            $statusCode = 403;
            $errorCode = 'FORBIDDEN';
            $message = 'This action is unauthorized.';
        } elseif ($type->isInstanceOf(ThrottleRequestsException::class)) {
            $statusCode = 429;
            $errorCode = 'TOO_MANY_REQUESTS';
            $message = 'Too Many Attempts.';
        } elseif ($type->isInstanceOf(NotFoundHttpException::class) || $type->isInstanceOf(ModelNotFoundException::class)) {
            $statusCode = 404;
            $errorCode = 'NOT_FOUND';
            $message = 'Resource not found.';
        } else {
            // For general unhandled system exceptions
            $errorCode = 'INTERNAL_ERROR';
            $message = 'Server Error';
        }

        $responseBodyType = (new OpenApiTypes\ObjectType)
            ->addProperty(
                'error_code',
                (new OpenApiTypes\StringType)
                    ->setDescription('The global error code.')
                    ->example($errorCode)
            )
            ->addProperty(
                'exception_type',
                (new OpenApiTypes\StringType)
                    ->setDescription('The exception class name.')
                    ->example($baseName)
            )
            ->addProperty(
                'message',
                (new OpenApiTypes\StringType)
                    ->setDescription('A human-readable error message.')
                    ->example($message)
            )
            ->setRequired(['error_code', 'exception_type', 'message']);

        return Response::make($statusCode)
            ->setDescription($errorCode . ' Error')
            ->setContent(
                'application/json',
                Schema::fromType($responseBodyType),
            );
    }
}
