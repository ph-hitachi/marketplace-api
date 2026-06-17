<?php

namespace Tests\Unit;

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
use Tests\TestCase;

class ExceptionsTest extends TestCase
{
    public function test_account_deactivated_exception_renders_correct_json_format()
    {
        Route::get('/api/test-exception-account-deactivated', function () {
            throw new AccountDeactivatedException();
        });

        $response = $this->getJson('/api/test-exception-account-deactivated');

        $response->assertStatus(403)
                 ->assertJson([
                     'error_code'     => 'ACCOUNT_DEACTIVATED',
                     'exception_type' => 'AccountDeactivatedException',
                 ]);
    }

    public function test_insufficient_balance_exception_renders_correct_json_format()
    {
        Route::get('/api/test-exception-insufficient-balance', function () {
            throw new InsufficientBalanceException();
        });

        $response = $this->getJson('/api/test-exception-insufficient-balance');

        $response->assertStatus(422)
                 ->assertJson([
                     'error_code'     => 'INSUFFICIENT_BALANCE',
                     'exception_type' => 'InsufficientBalanceException',
                 ]);
    }

    public function test_insufficient_stock_exception_renders_correct_json_format()
    {
        Route::get('/api/test-exception-insufficient-stock', function () {
            throw new InsufficientStockException('Test Product', 5);
        });

        $response = $this->getJson('/api/test-exception-insufficient-stock');

        $response->assertStatus(422)
                 ->assertJson([
                     'error_code'     => 'INSUFFICIENT_STOCK',
                     'exception_type' => 'InsufficientStockException',
                 ]);
    }

    public function test_invalid_credentials_exception_renders_correct_json_format()
    {
        Route::get('/api/test-exception-invalid-credentials', function () {
            throw new InvalidCredentialsException();
        });

        $response = $this->getJson('/api/test-exception-invalid-credentials');

        $response->assertStatus(401)
                 ->assertJson([
                     'error_code'     => 'INVALID_CREDENTIALS',
                     'exception_type' => 'InvalidCredentialsException',
                 ]);
    }

    public function test_invalid_status_transition_exception_renders_correct_json_format()
    {
        Route::get('/api/test-exception-invalid-status-transition', function () {
            throw new InvalidStatusTransitionException('pending', 'delivered');
        });

        $response = $this->getJson('/api/test-exception-invalid-status-transition');

        $response->assertStatus(409)
                 ->assertJson([
                     'error_code'     => 'INVALID_STATUS_TRANSITION',
                     'exception_type' => 'InvalidStatusTransitionException',
                 ]);
    }

    public function test_order_in_transit_exception_renders_correct_json_format()
    {
        Route::get('/api/test-exception-order-in-transit', function () {
            throw new OrderInTransitException();
        });

        $response = $this->getJson('/api/test-exception-order-in-transit');

        $response->assertStatus(409)
                 ->assertJson([
                     'error_code'     => 'ORDER_IN_TRANSIT',
                     'exception_type' => 'OrderInTransitException',
                 ]);
    }

    public function test_product_unavailable_exception_renders_correct_json_format()
    {
        Route::get('/api/test-exception-product-unavailable', function () {
            throw new ProductUnavailableException(1);
        });

        $response = $this->getJson('/api/test-exception-product-unavailable');

        $response->assertStatus(422)
                 ->assertJson([
                     'error_code'     => 'PRODUCT_UNAVAILABLE',
                     'exception_type' => 'ProductUnavailableException',
                 ]);
    }

    public function test_unexpected_error_exception_renders_correct_json_format()
    {
        Route::get('/api/test-exception-unexpected-error', function () {
            throw new UnexpectedErrorException('Custom unexpected error');
        });

        $response = $this->getJson('/api/test-exception-unexpected-error');

        $response->assertStatus(500)
                 ->assertJson([
                     'error_code'     => 'SERVER_ERROR',
                     'exception_type' => 'UnexpectedErrorException',
                     'message'        => 'Custom unexpected error',
                 ]);
    }

    public function test_user_delete_blocked_exception_renders_correct_json_format()
    {
        Route::get('/api/test-exception-user-delete-blocked', function () {
            throw new UserDeleteBlockedException();
        });

        $response = $this->getJson('/api/test-exception-user-delete-blocked');

        $response->assertStatus(409)
                 ->assertJson([
                     'error_code'     => 'DELETE_BLOCKED',
                     'exception_type' => 'UserDeleteBlockedException',
                 ]);
    }

    public function test_authentication_exception_renders_correct_json_format()
    {
        Route::get('/api/test-exception-auth', function () {
            throw new \Illuminate\Auth\AuthenticationException();
        });

        $response = $this->getJson('/api/test-exception-auth');

        $response->assertStatus(401)
                 ->assertJson([
                     'error_code'     => 'UNAUTHENTICATED',
                     'exception_type' => 'AuthenticationException',
                     'message'        => 'You are not authenticated. Please provide a valid Bearer token.',
                 ]);
    }

    public function test_authorization_exception_renders_correct_json_format()
    {
        Route::get('/api/test-exception-authz', function () {
            throw new \Illuminate\Auth\Access\AuthorizationException();
        });

        $response = $this->getJson('/api/test-exception-authz');

        // Render maps AuthorizationException to AccessDeniedHttpException or directly, resulting in HTTP 403 Forbidden
        $response->assertStatus(403)
                 ->assertJson([
                     'error_code'     => 'FORBIDDEN',
                     'exception_type' => 'AccessDeniedHttpException',
                     'message'        => 'You do not have permission to perform this action.',
                 ]);
    }

    public function test_access_denied_http_exception_renders_correct_json_format()
    {
        Route::get('/api/test-exception-access-denied', function () {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
        });

        $response = $this->getJson('/api/test-exception-access-denied');

        $response->assertStatus(403)
                 ->assertJson([
                     'error_code'     => 'FORBIDDEN',
                     'exception_type' => 'AccessDeniedHttpException',
                     'message'        => 'You do not have permission to perform this action.',
                 ]);
    }

    public function test_not_found_http_exception_renders_correct_json_format()
    {
        Route::get('/api/test-exception-not-found', function () {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        });

        $response = $this->getJson('/api/test-exception-not-found');

        $response->assertStatus(404)
                 ->assertJson([
                     'error_code'     => 'NOT_FOUND',
                     'exception_type' => 'NotFoundHttpException',
                     'message'        => 'The requested endpoint does not exist.',
                 ]);
    }

    public function test_model_not_found_exception_renders_correct_json_format()
    {
        Route::get('/api/test-exception-model-not-found', function () {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
        });

        $response = $this->getJson('/api/test-exception-model-not-found');

        $response->assertStatus(404)
                 ->assertJson([
                     'error_code'     => 'NOT_FOUND',
                     'exception_type' => 'ModelNotFoundException',
                     'message'        => 'The requested resource was not found.',
                 ]);
    }

    public function test_validation_exception_renders_correct_json_format()
    {
        Route::get('/api/test-exception-validation', function () {
            throw new \Illuminate\Validation\ValidationException(\Illuminate\Support\Facades\Validator::make([], ['field' => 'required']));
        });

        $response = $this->getJson('/api/test-exception-validation');

        $response->assertStatus(422)
                 ->assertJson([
                     'error_code'     => 'VALIDATION_ERROR',
                     'exception_type' => 'ValidationException',
                     'message'        => 'The given data was invalid.',
                 ]);
    }

    public function test_too_many_requests_http_exception_renders_correct_json_format()
    {
        Route::get('/api/test-exception-too-many-requests', function () {
            throw new \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException();
        });

        $response = $this->getJson('/api/test-exception-too-many-requests');

        $response->assertStatus(429)
                 ->assertJson([
                     'error_code'     => 'TOO_MANY_REQUESTS',
                     'exception_type' => 'TooManyRequestsHttpException',
                     'message'        => 'Too many requests. Please slow down and try again in a moment.',
                 ]);
    }

    public function test_fallback_500_error_renders_unexpected_error_exception_format()
    {
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
}
