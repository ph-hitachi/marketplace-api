<?php

namespace Tests\Feature;

use App\Exceptions\AccountDeactivatedException;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\InvalidStatusTransitionException;
use App\Exceptions\OrderInTransitException;
use App\Exceptions\ProductUnavailableException;
use App\Exceptions\UnexpectedErrorException;
use App\Exceptions\UserDeleteBlockedException;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExceptionsTest extends TestCase
{
    #[DataProvider('exceptionsProvider')]
    public function test_exception_renders_correct_json_format(\Closure $exceptionFactory, int $expectedStatus, string $expectedErrorCode, string $expectedType, ?string $expectedMessage = null)
    {
        $exception = $exceptionFactory();

        // Define a temporary route that throws the given exception
        Route::get('/api/test-exception', function () use ($exception) {
            throw $exception;
        });

        // Hit the route and assert the response structure
        $response = $this->getJson('/api/test-exception');

        $response->assertStatus($expectedStatus)
                 ->assertJson([
                     'error_code'     => $expectedErrorCode,
                     'exception_type' => $expectedType,
                     'message'        => $expectedMessage ?? $exception->getMessage(),
                 ]);
    }

    public function test_fallback_500_error_renders_unexpected_error_exception_format()
    {
        // Define a route that throws a standard unhandled exception
        Route::get('/api/test-fallback-exception', function () {
            throw new \Exception('This is a completely random unhandled error.');
        });

        $response = $this->getJson('/api/test-fallback-exception');

        $response->assertStatus(500)
                 ->assertJson([
                     'error_code'     => 'SERVER_ERROR',
                     'exception_type' => 'UnexpectedErrorException',
                     'message'        => 'Sorry, something went wrong on the server. Please try again later.',
                 ]);
    }

    public static function exceptionsProvider(): array
    {
        return [
            'AccountDeactivatedException' => [
                fn() => new AccountDeactivatedException(),
                403,
                'ACCOUNT_DEACTIVATED',
                'AccountDeactivatedException'
            ],
            'InsufficientBalanceException' => [
                fn() => new InsufficientBalanceException(),
                422,
                'INSUFFICIENT_BALANCE',
                'InsufficientBalanceException'
            ],
            'InsufficientStockException' => [
                fn() => new InsufficientStockException('Test Product', 5),
                422,
                'INSUFFICIENT_STOCK',
                'InsufficientStockException'
            ],
            'InvalidCredentialsException' => [
                fn() => new InvalidCredentialsException(),
                401,
                'INVALID_CREDENTIALS',
                'InvalidCredentialsException'
            ],
            'InvalidStatusTransitionException' => [
                fn() => new InvalidStatusTransitionException('pending', 'delivered'),
                409,
                'INVALID_STATUS_TRANSITION',
                'InvalidStatusTransitionException'
            ],
            'OrderInTransitException' => [
                fn() => new OrderInTransitException(),
                409,
                'ORDER_IN_TRANSIT',
                'OrderInTransitException'
            ],
            'ProductUnavailableException' => [
                fn() => new ProductUnavailableException(1),
                422,
                'PRODUCT_UNAVAILABLE',
                'ProductUnavailableException'
            ],
            'UnexpectedErrorException' => [
                fn() => new UnexpectedErrorException('Custom unexpected error'),
                500,
                'SERVER_ERROR',
                'UnexpectedErrorException'
            ],
            'UserDeleteBlockedException' => [
                fn() => new UserDeleteBlockedException(),
                409,
                'DELETE_BLOCKED',
                'UserDeleteBlockedException'
            ],
            'AuthenticationException' => [
                fn() => new \Illuminate\Auth\AuthenticationException(),
                401,
                'UNAUTHENTICATED',
                'AuthenticationException',
                'You are not authenticated. Please provide a valid Bearer token.'
            ],
            'AuthorizationException' => [
                fn() => new \Illuminate\Auth\Access\AuthorizationException(),
                403,
                'FORBIDDEN',
                'AccessDeniedHttpException',
                'You do not have permission to perform this action.'
            ],
            'AccessDeniedHttpException' => [
                fn() => new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException(),
                403,
                'FORBIDDEN',
                'AccessDeniedHttpException',
                'You do not have permission to perform this action.'
            ],
            'NotFoundHttpException' => [
                fn() => new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException(),
                404,
                'NOT_FOUND',
                'NotFoundHttpException',
                'The requested endpoint does not exist.'
            ],
            'ModelNotFoundException' => [
                fn() => new \Illuminate\Database\Eloquent\ModelNotFoundException(),
                404,
                'NOT_FOUND',
                'ModelNotFoundException',
                'The requested resource was not found.'
            ],
            'ValidationException' => [
                fn() => new \Illuminate\Validation\ValidationException(\Illuminate\Support\Facades\Validator::make([], ['field' => 'required'])),
                422,
                'VALIDATION_ERROR',
                'ValidationException',
                'The given data was invalid.'
            ],
            'TooManyRequestsHttpException' => [
                fn() => new \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException(),
                429,
                'TOO_MANY_REQUESTS',
                'TooManyRequestsHttpException',
                'Too many requests. Please slow down and try again in a moment.'
            ]
        ];
    }
}
