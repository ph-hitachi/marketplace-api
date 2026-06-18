# Marketplace API — Project Overview

This document provides a complete and detailed overview of the application's directory structure, the full routes outline from `routes/api.php`, and the latest test execution results.

---

## 1. Complete Folder Structure

```text
src/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── ExportApiDocs.php
│   │       ├── GenerateOverview.php
│   │       ├── GenerateOverviewDocs.php
│   │       └── GeneratePostmanCollection.php
│   ├── Docs/
│   │   ├── GlobalExceptionExtension.php
│   │   ├── UnexpectedErrorExceptionExtension.php
│   │   └── ValidationExceptionExtension.php
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
│   │   │       │   ├── CartController.php
│   │   │       │   └── OrderController.php
│   │   │       ├── Seller/
│   │   │       │   ├── OrderController.php
│   │   │       │   ├── ProductController.php
│   │   │       │   ├── SellerProfileController.php
│   │   │       │   └── ShopController.php
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
│   │       ├── Cart/
│   │       │   └── StoreCartRequest.php
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
│   │   ├── OrderItem.php
│   │   ├── Product.php
│   │   ├── Shop.php
│   │   ├── User.php
│   │   ├── Wallet.php
│   │   └── WalletTransaction.php
│   ├── Policies/
│   │   ├── AddressPolicy.php
│   │   ├── OrderPolicy.php
│   │   ├── ProductPolicy.php
│   │   ├── ShopPolicy.php
│   │   ├── UserPolicy.php
│   │   └── WalletPolicy.php
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   └── Services/
│       ├── OrderPaymentService.php
│       ├── OrderService.php
│       └── ProductStockService.php
├── config/
│   ├── auth.php
│   ├── jwt.php
│   ├── scramble.php
│   └── ...
├── database/
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   ├── 2026_01_01_000010_create_shops_table.php
│   │   ├── 2026_01_01_000020_create_addresses_table.php
│   │   ├── 2026_01_01_000030_create_products_table.php
│   │   ├── 2026_01_01_000040_create_orders_table.php
│   │   ├── 2026_01_01_000060_create_wallet_transactions_table.php
│   │   ├── 2026_06_16_040610_create_order_items_table.php
│   │   └── 2026_06_16_040616_create_carts_table.php
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
    │   │   ├── ProductsTest.php
    │   │   └── ShopsTest.php
    │   ├── Seller/
    │   │   ├── OrdersTest.php
    │   │   └── ProductsTest.php
    │   ├── User/
    │   │   ├── ProfileTest.php
    │   │   └── WalletTest.php
    │   └── ExceptionsTest.php
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

Route::get('/shops',                 [ShopController::class, 'index']);
Route::get('/shops/{shop}',          [ShopController::class, 'show']);

// ── Authenticated routes (JWT — any role) ──────────────────────────────────
Route::middleware(['auth:api', 'active'])->group(function () {

    Route::post('/auth/logout',  [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);

    // ── Shared: Customer & Seller (wallet, profile, me) ─────────────────────
    Route::middleware('role:customer,seller')->prefix('user')->group(function () {
        Route::get('/me',                        [AuthController::class, 'me']);
        Route::get('/wallets',                   [WalletController::class, 'index']);
        Route::post('/wallets',                  [WalletController::class, 'store']);
        Route::get('/wallets/{wallet}',          [WalletController::class, 'show']);
        Route::post('/wallets/{wallet}/topup',   [WalletController::class, 'topup']);
        Route::post('/wallets/{wallet}/default', [WalletController::class, 'setDefault']);
        Route::put('/profile',                   [UserProfileController::class, 'update']);
    });

    // ── Customer ──────────────────────────────────────────────────────────
    Route::middleware('role:customer')->prefix('customer')->group(function () {

        // Addresses
        Route::get('/addresses',                     [AddressController::class, 'index']);
        Route::post('/addresses',                    [AddressController::class, 'store']);
        Route::get('/addresses/{address}',           [AddressController::class, 'show']);
        Route::put('/addresses/{address}',           [AddressController::class, 'update']);
        Route::delete('/addresses/{address}',        [AddressController::class, 'destroy']);
        Route::patch('/addresses/{address}/default', [AddressController::class, 'setDefault']);

        // Carts (Bookmark / Saved items)
        Route::get('/carts',            [CartController::class, 'index']);
        Route::post('/carts',           [CartController::class, 'store']);
        Route::delete('/carts/{cart}',  [CartController::class, 'destroy']);

        // Orders
        Route::post('/orders',                    [CustomerOrderController::class, 'store']);
        Route::get('/orders',                     [CustomerOrderController::class, 'index']);
        Route::get('/orders/{order}',             [CustomerOrderController::class, 'show']);
        Route::patch('/orders/{order}/cancel',    [CustomerOrderController::class, 'cancel']);
        Route::post('/orders/{order}/confirm',    [CustomerOrderController::class, 'confirm']);
    });

    // ── Seller ────────────────────────────────────────────────────────────
    Route::middleware('role:seller')->prefix('seller')->group(function () {

        // Profile
        Route::put('/profile',            [SellerProfileController::class, 'update']);

        // Products
        Route::get('/products',                          [SellerProductController::class, 'index']);
        Route::post('/products',                         [SellerProductController::class, 'store']);
        Route::get('/products/{product}',                [SellerProductController::class, 'show']);
        Route::put('/products/{product}',                [SellerProductController::class, 'update']);
        Route::delete('/products/{product}',             [SellerProductController::class, 'destroy']);
        Route::patch('/products/{product}/activate',     [SellerProductController::class, 'activate']);
        Route::patch('/products/{product}/deactivate',   [SellerProductController::class, 'deactivate']);

        // Orders
        Route::get('/orders',                     [SellerOrderController::class, 'index']);
        Route::get('/orders/{order}',             [SellerOrderController::class, 'show']);
        Route::patch('/orders/{order}/status',    [SellerOrderController::class, 'updateStatus']);
        Route::patch('/orders/{order}/cancel',    [SellerOrderController::class, 'cancel']);
    });

    // ── Admin ─────────────────────────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->group(function () {

        // Users
        Route::get('/users',                      [AdminUserController::class, 'index']);
        Route::get('/users/{user}',               [AdminUserController::class, 'show']);
        Route::patch('/users/{user}/activate',    [AdminUserController::class, 'activate']);
        Route::patch('/users/{user}/deactivate',  [AdminUserController::class, 'deactivate']);
        Route::delete('/users/{user}',            [AdminUserController::class, 'destroy']);

        // Orders
        Route::get('/orders',               [AdminOrderController::class, 'index']);
        Route::get('/orders/{order}',       [AdminOrderController::class, 'show']);
    });
});
```

---

## 3. Test Results

The application features a comprehensive automated test suite covering all critical roles, API endpoints, and logic flows.

**Latest Run (99 Tests Passed successfully):**

```
  Tests:    99 passed (340 assertions)
  Duration: 39.18s
```

### Coverage Summary

| Test Suite | Tests | Focus |
|---|---|---|
| `Admin\OrdersTest` | 3 | Admin order visibility & status restrictions |
| `Admin\UsersTest` | 6 | User CRUD, activate/deactivate, delete guards |
| `Customer\AddressTest` | 4 | Address CRUD, IDOR protection |
| `Customer\OrdersTest` | 15 | Full order lifecycle, payments, cancellations, IDOR |
| `Public\AuthTest` | 11 | Register, login, logout, profile |
| `Public\ProductsTest` | 3 | Public product listing and visibility |
| `Public\ShopsTest` | 2 | Public shop list and shop details visibility |
| `Seller\OrdersTest` | 8 | Seller order management, status transitions |
| `Seller\ProductsTest` | 5 | Product CRUD, activate/deactivate, ownership |
| `User\ProfileTest` | 5 | Profile update, role protection, seller shop |
| `User\WalletTest` | 6 | Wallet CRUD, topups, IDOR protection |
| `Unit\ExceptionsTest` | 17 | JSON error format for every domain exception |
| `Unit\JwtTokenTest` | 13 | JWT authentication, refresh, blacklist, expiration |
| `Unit\ExampleTest` | 1 | Unit test baseline |
| **Total** | **99** | **340 assertions** |

---

## 4. Error Handling & Exceptions

The API maintains a unified JSON error response format for all failures:

```json
{
  "error_code": "SNAKE_CASE_CODE",
  "message": "Human-readable description."
}
```

### Global HTTP Errors
These are standard framework or HTTP-level exceptions captured globally:

| HTTP Code | Error Code | Exception Class | Description |
|---|---|---|---|
| `401` | `UNAUTHENTICATED` | `AuthenticationException` | No Bearer token or invalid token. |
| `403` | `UNAUTHORIZED` | `AuthorizationException` | Action forbidden (wrong role or policy restriction). |
| `404` | `ROUTE_NOT_FOUND` | `NotFoundHttpException` | The requested route does not exist. |
| `405` | `METHOD_NOT_ALLOWED` | `MethodNotAllowedHttpException` | The HTTP method is not supported for the route. |
| `422` | `VALIDATION_ERROR` | `ValidationException` | Field validation failed. |
| `429` | `TOO_MANY_REQUESTS` | `TooManyRequestsHttpException` | Rate limit exceeded (60 requests/minute). |
| `500` | `SERVER_ERROR` | `Throwable` (catch-all) | Unexpected internal server error. |

### Domain-Specific Exceptions
These are custom exceptions mapped to business rules within the application:

| HTTP Code | Error Code | Exception Class | When it happens |
|---|---|---|---|
| `400` | `INSUFFICIENT_BALANCE` | `InsufficientBalanceException` | Wallet has insufficient funds during purchase. |
| `401` | `INVALID_CREDENTIALS` | `InvalidCredentialsException` | Login email/password mismatch. |
| `403` | `ACCOUNT_DEACTIVATED` | `AccountDeactivatedException` | Attempting to access resource with a blocked account. |
| `409` | `INVALID_STATUS_TRANSITION` | `InvalidStatusTransitionException` | Advancing an order status out of order or setting an invalid state. |
| `409` | `ORDER_IN_TRANSIT` | `OrderInTransitException` | Attempting to cancel an order that has already been shipped. |
| `409` | `USER_DELETE_BLOCKED` | `UserDeleteBlockedException` | Admin trying to delete a user with active or processing orders. |
| `422` | `INSUFFICIENT_STOCK` | `InsufficientStockException` | Buying quantity exceeding available product stock. |
| `422` | `PRODUCT_UNAVAILABLE` | `ProductUnavailableException` | Ordering an inactive or soft-deleted product. |

