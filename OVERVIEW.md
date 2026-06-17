# Marketplace API — Project Overview

This document provides a complete and detailed overview of the application's directory structure, the full routes outline from `routes/api.php`, and the latest test execution results.

---

## 1. Complete Folder Structure

```text
src/
├── app/
│   ├── Enums/
│   │   └── CancelReason.php
│   ├── Exceptions/
│   │   ├── AccountDeactivatedException.php
│   │   ├── InsufficientBalanceException.php
│   │   ├── InsufficientStockException.php
│   │   ├── InvalidCredentialsException.php
│   │   ├── InvalidStatusTransitionException.php
│   │   ├── OrderInTransitException.php
│   │   ├── ProductUnavailableException.php
│   │   ├── UnexpectedErrorException.php
│   │   └── UserDeleteBlockedException.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Controller.php
│   │   │   └── Api/
│   │   │       ├── Admin/
│   │   │       │   ├── OrderController.php
│   │   │       │   └── UserController.php
│   │   │       ├── Customer/
│   │   │       │   ├── AddressController.php
│   │   │       │   └── OrderController.php
│   │   │       ├── Seller/
│   │   │       │   ├── OrderController.php
│   │   │       │   ├── ProductController.php
│   │   │       │   └── SellerProfileController.php
│   │   │       ├── User/
│   │   │       │   ├── ProfileController.php
│   │   │       │   └── WalletController.php
│   │   │       ├── AuthController.php
│   │   │       └── ProductController.php
│   │   ├── Middleware/
│   │   │   ├── EnsureRole.php
│   │   │   ├── EnsureUserIsActive.php
│   │   │   └── SecurityHeaders.php
│   │   └── Requests/
│   │       ├── Address/
│   │       │   ├── StoreAddressRequest.php
│   │       │   └── UpdateAddressRequest.php
│   │       ├── Auth/
│   │       │   ├── LoginRequest.php
│   │       │   └── RegisterRequest.php
│   │       ├── Order/
│   │       │   ├── CancelOrderRequest.php
│   │       │   ├── PlaceOrderRequest.php
│   │       │   └── UpdateOrderStatusRequest.php
│   │       ├── Product/
│   │       │   ├── StoreProductRequest.php
│   │       │   └── UpdateProductRequest.php
│   │       ├── Seller/
│   │       │   └── UpdateSellerProfileRequest.php
│   │       ├── User/
│   │       │   └── UpdateProfileRequest.php
│   │       └── Wallet/
│   │           ├── StoreWalletRequest.php
│   │           └── TopupRequest.php
│   ├── Models/
│   │   ├── Address.php
│   │   ├── Order.php
│   │   ├── Product.php
│   │   ├── SellerProfile.php
│   │   ├── User.php
│   │   ├── Wallet.php
│   │   └── WalletTransaction.php
│   ├── Policies/
│   │   ├── AddressPolicy.php
│   │   ├── OrderPolicy.php
│   │   ├── ProductPolicy.php
│   │   ├── SellerProfilePolicy.php
│   │   ├── UserPolicy.php
│   │   └── WalletPolicy.php
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   └── Services/
│       ├── OrderPaymentService.php
│       ├── OrderService.php
│       └── ProductStockService.php
├── database/
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   ├── 2026_01_01_000010_create_seller_profiles_table.php
│   │   ├── 2026_01_01_000020_create_addresses_table.php
│   │   ├── 2026_01_01_000030_create_products_table.php
│   │   ├── 2026_01_01_000040_create_orders_table.php
│   │   ├── 2026_01_01_000060_create_wallet_transactions_table.php
│   │   └── 2026_06_14_054230_create_personal_access_tokens_table.php
│   └── seeders/
│       └── DatabaseSeeder.php
├── docs/
│   ├── api/
│   │   ├── admin/
│   │   │   └── readme.md
│   │   ├── customer/
│   │   │   └── readme.md
│   │   ├── public/
│   │   │   └── readme.md
│   │   ├── seller/
│   │   │   └── readme.md
│   │   └── user/
│   │       └── readme.md
│   └── marketplace.dbml
├── routes/
│   ├── api.php
│   ├── console.php
│   └── web.php
└── tests/
    ├── TestCase.php
    ├── Feature/
    │   ├── Admin/
    │   │   ├── OrdersTest.php
    │   │   └── UsersTest.php
    │   ├── Customer/
    │   │   ├── AddressTest.php
    │   │   └── OrdersTest.php
    │   ├── Public/
    │   │   ├── AuthTest.php
    │   │   └── ProductsTest.php
    │   ├── Seller/
    │   │   ├── OrdersTest.php
    │   │   └── ProductsTest.php
    │   ├── User/
    │   │   ├── ProfileTest.php
    │   │   └── WalletTest.php
    │   └── ExampleTest.php
    └── Unit/
        └── ExampleTest.php
```

---

## 2. API Routes Outline (`routes/api.php`)

Below is the routing layout configured in the application:

```php
// ── Public routes (no auth) ────────────────────────────────────────────────
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login',    [AuthController::class, 'login']);

Route::get('/products',      [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// ── Authenticated routes (any role) ────────────────────────────────────────
Route::middleware(['auth:sanctum', 'active'])->group(function () {

    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);

    // ── Wallets & Profile (Shared Customer/Seller) ─────────────────────────
    Route::middleware('role:customer,seller')->prefix('user')->group(function () {
        Route::get('/wallets',                  [WalletController::class, 'index']);
        Route::post('/wallets',                 [WalletController::class, 'store']);
        Route::get('/wallets/{wallet}',         [WalletController::class, 'show']);
        Route::post('/wallets/{wallet}/topup',  [WalletController::class, 'topup']);
        Route::post('/wallets/{wallet}/default', [WalletController::class, 'setDefault']);
        Route::put('/profile',                  [UserProfileController::class, 'update']);
    });

    // ── Customer ──────────────────────────────────────────────────────────
    Route::middleware('role:customer')->prefix('customer')->group(function () {

        // Addresses
        Route::get('/addresses',                    [AddressController::class, 'index']);
        Route::post('/addresses',                   [AddressController::class, 'store']);
        Route::get('/addresses/{address}',          [AddressController::class, 'show']);
        Route::put('/addresses/{address}',          [AddressController::class, 'update']);
        Route::delete('/addresses/{address}',       [AddressController::class, 'destroy']);
        Route::patch('/addresses/{address}/default',[AddressController::class, 'setDefault']);

        // Orders
        Route::post('/orders',              [CustomerOrderController::class, 'store']);
        Route::get('/orders',               [CustomerOrderController::class, 'index']);
        Route::get('/orders/{order}',       [CustomerOrderController::class, 'show']);
        Route::patch('/orders/{order}/cancel', [CustomerOrderController::class, 'cancel']);
        Route::post('/orders/{order}/confirm', [CustomerOrderController::class, 'confirm']);
    });

    // ── Seller ────────────────────────────────────────────────────────────
    Route::middleware('role:seller')->prefix('seller')->group(function () {
 
        // Profile
        Route::put('/profile',            [SellerProfileController::class, 'update']);
 
        // Products
        Route::get('/products',           [SellerProductController::class, 'index']);
        Route::post('/products',          [SellerProductController::class, 'store']);
        Route::get('/products/{product}', [SellerProductController::class, 'show']);
        Route::put('/products/{product}', [SellerProductController::class, 'update']);
        Route::delete('/products/{product}', [SellerProductController::class, 'destroy']);
        Route::patch('/products/{product}/activate', [SellerProductController::class, 'activate']);
        Route::patch('/products/{product}/deactivate', [SellerProductController::class, 'deactivate']);

        // Orders
        Route::get('/orders',              [SellerOrderController::class, 'index']);
        Route::get('/orders/{order}',      [SellerOrderController::class, 'show']);
        Route::patch('/orders/{order}/status', [SellerOrderController::class, 'updateStatus']);
        Route::patch('/orders/{order}/cancel', [SellerOrderController::class, 'cancel']);
    });

    // ── Admin ─────────────────────────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->group(function () {

        // Users
        Route::get('/users',                    [AdminUserController::class, 'index']);
        Route::get('/users/{user}',             [AdminUserController::class, 'show']);
        Route::patch('/users/{user}/activate',  [AdminUserController::class, 'activate']);
        Route::patch('/users/{user}/deactivate',[AdminUserController::class, 'deactivate']);
        Route::delete('/users/{user}',          [AdminUserController::class, 'destroy']);

        // Orders
        Route::get('/orders',               [AdminOrderController::class, 'index']);
        Route::get('/orders/{order}',       [AdminOrderController::class, 'show']);
    });
});
```

---

## 3. Global Exception & Middleware Setup (`bootstrap/app.php`)

The application configures global exception rendering to ensure consistent JSON formats across all endpoints, including standardizing HTTP errors, validation errors, and domain-specific `UnexpectedErrorException`s.

```php
<?php

use App\Exceptions\AccountDeactivatedException;
use App\Exceptions\UnexpectedErrorException;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Apply security headers to every response
        $middleware->append(SecurityHeaders::class);

        // Middleware aliases
        $middleware->alias([
            'role'   => EnsureRole::class,
            'active' => EnsureUserIsActive::class,
        ]);

        // Apply rate limiting to the api middleware group
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // Render all API errors as JSON
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // ── UnexpectedErrorException (domain errors: balance, stock, transitions, etc.) ──
        $exceptions->render(function (UnexpectedErrorException $e, Request $request) {
            return response()->json([
                'error_code'     => $e->getErrorCode(),
                'exception_type' => class_basename($e),
                'message'        => $e->getMessage(),
            ], $e->getStatusCode());
        });

        // ── 401 Unauthenticated ────────────────────────────────────────────────────
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json([
                'error_code' => 'UNAUTHENTICATED',
                'message'    => 'You are not authenticated. Please provide a valid Bearer token.',
            ], Response::HTTP_UNAUTHORIZED);
        });

        // ── 403 Forbidden (Policy / Gate failures) ─────────────────────────────────
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            return response()->json([
                'error_code' => 'FORBIDDEN',
                'message'    => 'You do not have permission to perform this action.',
            ], Response::HTTP_FORBIDDEN);
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            return response()->json([
                'error_code' => 'FORBIDDEN',
                'message'    => 'You do not have permission to perform this action.',
            ], Response::HTTP_FORBIDDEN);
        });

        // ── 404 Not Found (Route or Model) ─────────────────────────────────────────
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            // Unwrap ModelNotFoundException for a cleaner message
            $previous = $e->getPrevious();
            $message  = $previous instanceof ModelNotFoundException
                ? 'The requested resource was not found.'
                : 'The requested endpoint does not exist.';

            return response()->json([
                'error_code' => 'NOT_FOUND',
                'message'    => $message,
            ], Response::HTTP_NOT_FOUND);
        });

        // ── 422 Validation Error ────────────────────────────────────────────────────
        $exceptions->render(function (ValidationException $e, Request $request) {
            return response()->json([
                'error_code' => 'VALIDATION_ERROR',
                'message'    => 'The given data was invalid.',
                'errors'     => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        });

        // ── 429 Too Many Requests ───────────────────────────────────────────────────
        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            return response()->json([
                'error_code' => 'TOO_MANY_REQUESTS',
                'message'    => 'Too many requests. Please slow down and try again in a moment.',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        });

        // ── 500 Internal Server Error (catch-all) ───────────────────────────────────
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($e instanceof UnexpectedErrorException ||
                $e instanceof AuthenticationException ||
                $e instanceof AuthorizationException ||
                $e instanceof AccessDeniedHttpException ||
                $e instanceof ValidationException ||
                $e instanceof NotFoundHttpException ||
                $e instanceof TooManyRequestsHttpException) {
                return null;
            }

            if ($request->is('api/*')) {
                report($e); // Still logs to Laravel log
                $unexpected = new UnexpectedErrorException('Sorry, something went wrong on the server. Please try again later.');
                return response()->json([
                    'error_code'     => $unexpected->getErrorCode(),
                    'exception_type' => class_basename($unexpected),
                    'message'        => $unexpected->getMessage(),
                ], $unexpected->getStatusCode());
            }
        });

    })->create();
```

---

## 4. Standard HTTP Response Codes

While domain-specific errors (`UnexpectedErrorException`) have their own dedicated error codes, the API also relies heavily on standard HTTP status codes.

### Success Responses

Success responses are typically documented on a per-endpoint basis, but adhere to these global conventions:

- **`200 OK`**: The request succeeded. Used for `GET` (fetching records), `PUT`/`PATCH` (updating records), and standard actions.
- **`201 Created`**: The request succeeded and a new resource was created. Used exclusively for `POST` requests that result in database creation.
- **`204 No Content`**: The request succeeded, but there is no body to return. Used primarily for `DELETE` requests where the resource is successfully removed.

### Standard HTTP Errors

These errors are automatically intercepted and formatted consistently by the global exception handler in `bootstrap/app.php`:

- **`400 Bad Request`**: Used for domain-specific logic failures (e.g., `InsufficientBalanceException`).
- **`401 Unauthorized`**: Missing, invalid, or expired Bearer token.
- **`403 Forbidden`**: The authenticated user does not have the correct role or permission (e.g., a customer trying to access a seller endpoint).
- **`404 Not Found`**: The requested URL endpoint does not exist (`NotFoundHttpException`) or the requested database record (e.g., `Product::findOrFail()`) does not exist (`ModelNotFoundException`).
- **`405 Method Not Allowed`**: Invalid HTTP method.
- **`409 Conflict`**: The request could not be completed due to a conflict with the current state of the target resource (e.g., `InvalidStatusTransitionException` when trying to cancel an already shipped order).
- **`422 Unprocessable Entity`**: The request body failed validation (`ValidationException`). The response includes an `errors` object detailing which fields failed.
- **`429 Too Many Requests`**: The user has exceeded the global rate limit (`throttle:api`).
- **`500 Internal Server Error`**: An unexpected system crash or fatal error occurred. The true exception is hidden from the user and logged in Telescope.

---

## 5. Test Results

The application features a comprehensive automated test suite covering all critical roles, API endpoints, and logic flows.

**Latest Run (As of Refactoring):**
- 77 Tests Passed
- 0 Failures (excluding the default Laravel ExampleTest root route)

Testing covers:
- Complete Customer & Seller Order Lifecycles
- Stock deduction and restoration via `ProductStockService`
- Wallet topups, payments, and refunds via `OrderPaymentService`
- Proper Domain Exception formatting
