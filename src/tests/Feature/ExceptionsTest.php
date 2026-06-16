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
    public function test_exception_renders_correct_json_format(\Exception $exception, int $expectedStatus, string $expectedErrorCode, string $expectedType)
    {
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
                     'message'        => $exception->getMessage(),
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
                new AccountDeactivatedException(),
                403,
                'ACCOUNT_DEACTIVATED',
                'AccountDeactivatedException'
            ],
            'InsufficientBalanceException' => [
                new InsufficientBalanceException(),
                422,
                'INSUFFICIENT_BALANCE',
                'InsufficientBalanceException'
            ],
            'InsufficientStockException' => [
                new InsufficientStockException('Test Product', 5),
                422,
                'INSUFFICIENT_STOCK',
                'InsufficientStockException'
            ],
            'InvalidCredentialsException' => [
                new InvalidCredentialsException(),
                401,
                'INVALID_CREDENTIALS',
                'InvalidCredentialsException'
            ],
            'InvalidStatusTransitionException' => [
                new InvalidStatusTransitionException('pending', 'delivered'),
                422,
                'INVALID_STATUS_TRANSITION',
                'InvalidStatusTransitionException'
            ],
            'OrderInTransitException' => [
                new OrderInTransitException(),
                422,
                'ORDER_IN_TRANSIT',
                'OrderInTransitException'
            ],
            'ProductUnavailableException' => [
                new ProductUnavailableException(1),
                422,
                'PRODUCT_UNAVAILABLE',
                'ProductUnavailableException'
            ],
            'UnexpectedErrorException' => [
                new UnexpectedErrorException('Custom unexpected error'),
                500,
                'SERVER_ERROR',
                'UnexpectedErrorException'
            ],
            'UserDeleteBlockedException' => [
                new UserDeleteBlockedException(),
                422,
                'DELETE_BLOCKED',
                'UserDeleteBlockedException'
            ],
        ];
    }
}
