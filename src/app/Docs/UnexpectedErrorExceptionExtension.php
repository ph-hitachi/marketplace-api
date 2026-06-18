<?php

namespace App\Docs;

use Dedoc\Scramble\Extensions\ExceptionToResponseExtension;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types as OpenApiTypes;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use App\Exceptions\UnexpectedErrorException;
use Illuminate\Support\Str;

class UnexpectedErrorExceptionExtension extends ExceptionToResponseExtension
{
    public function shouldHandle(Type $type)
    {
        return $type instanceof ObjectType
            && $type->isInstanceOf(UnexpectedErrorException::class);
    }

    public function toResponse(Type $type)
    {
        $className = ltrim($type->name, '\\');
        $baseName = class_basename($className);
        
        $statusCode = 422;
        $errorCode = 'DOMAIN_ERROR';
        $message = 'This action cannot be completed.';
        
        try {
            $reflection = new \ReflectionClass($className);
            if ($reflection->isInstantiable()) {
                $instance = $reflection->newInstanceWithoutConstructor();
                if ($instance instanceof UnexpectedErrorException) {
                    $statusCode = $instance->getStatusCode();
                    $errorCode = $instance->getErrorCode();
                    $message = $instance->getMessage() ?: $message;
                }
            }
        } catch (\Throwable $e) {
            // fallback
        }

        $responseBodyType = (new OpenApiTypes\ObjectType)
            ->addProperty(
                'error_code',
                (new OpenApiTypes\StringType)
                    ->setDescription('The domain-specific error code.')
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
            ->setDescription(Str::headline($baseName) . ' Error')
            ->setContent(
                'application/json',
                Schema::fromType($responseBodyType),
            );
    }
}
