# API Overview

Welcome to the Marketplace API documentation.

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

---

## 3. Global Error Responses

The API uses a standardized error format for all exceptions. The `app.php` bootstrap configuration enforces the following global error codes:

| Error Code | HTTP Status | Exception Type | Description |
|---|---|---|---|
| `UNAUTHENTICATED` | `401 Unauthorized` | `AuthenticationException` | You are not authenticated. Please provide a valid Bearer token. |
| `FORBIDDEN` | `403 Forbidden` | `AuthorizationException`, `AccessDeniedHttpException` | You do not have permission to perform this action. |
| `NOT_FOUND` | `404 Not Found` | `NotFoundHttpException`, `ModelNotFoundException` | The requested resource or endpoint does not exist. |
| `VALIDATION_ERROR` | `422 Unprocessable Entity` | `ValidationException` | The given data was invalid. Check the `errors` object for details. |
| `TOO_MANY_REQUESTS` | `429 Too Many Requests` | `TooManyRequestsHttpException` | Too many requests. Please slow down. |
| (Various Domain Codes) | `400` or `422` | `UnexpectedErrorException` | Business logic errors (e.g. `INSUFFICIENT_BALANCE`, `PRODUCT_UNAVAILABLE`). |
| `INTERNAL_ERROR` | `500 Internal Server Error` | `Throwable` | Unhandled server errors. |

### Global Error JSON Format

When a global error (like 401, 403, 404, 429, or 500) occurs, the API returns a structured JSON response matching the domain exception format:

```json
{
    "error_code": "UNAUTHENTICATED",
    "exception_type": "AuthenticationException",
    "message": "Unauthenticated."
}
```

### Example Error Response

```json
{
    "error_code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "errors": {
        "payment_method": [
            "The selected payment method is invalid."
        ]
    }
}
```

### Domain-Specific Error Codes

In addition to the standard HTTP errors, the API throws custom business logic exceptions. These return a consistent JSON payload containing the specific `error_code` and a human-readable `message`.

| Exception Class | HTTP Status | Error Code (`error_code`) | Typical Cause |
|---|---|---|---|
| `AccountDeactivatedException` | `403` | `ACCOUNT_DEACTIVATED` | Attempting to login or perform actions with a deactivated account. |
| `InsufficientBalanceException` | `422` | `INSUFFICIENT_BALANCE` | Placing an order when the wallet balance is too low. |
| `InsufficientStockException` | `422` | `INSUFFICIENT_STOCK` | Placing an order for a quantity that exceeds available inventory. |
| `InvalidCredentialsException` | `401` | `INVALID_CREDENTIALS` | Providing an incorrect password during login. |
| `InvalidStatusTransitionException` | `422` | `INVALID_STATUS_TRANSITION` | Attempting to move an order to an illogical state (e.g., pending to delivered). |
| `OrderInTransitException` | `422` | `ORDER_IN_TRANSIT` | Attempting to cancel an order that has already been shipped. |
| `ProductUnavailableException` | `422` | `PRODUCT_UNAVAILABLE` | Attempting to purchase a product that is inactive or deleted. |
| `UserDeleteBlockedException` | `422` | `DELETE_BLOCKED` | Attempting to delete a user that has active orders tied to it. |
| `UnexpectedErrorException` | `500` | `SERVER_ERROR` | A generic fallback for unhandled domain errors. |

### Domain Error JSON Format

When a domain exception is thrown, the API returns a structured JSON response:

```json
{
    "error_code": "INSUFFICIENT_BALANCE",
    "exception_type": "InsufficientBalanceException",
    "message": "Your wallet does not have enough balance to complete this transaction."
}
```

---

## 4. Rate Limiting & Security Policies

The Marketplace API is built with high security standards. Every response includes strict security headers and global rate limits to protect both customer data and system integrity.

### API Rate Limiting

The API applies a strict rate limit of **60 requests per minute** per IP Address or Authenticated User ID. 
When the limit is reached, the API returns a `429 Too Many Requests` status code (`TOO_MANY_REQUESTS` global error code).

Response headers included on every request to track your limit:
- `X-RateLimit-Limit`: Maximum requests allowed per minute (60)
- `X-RateLimit-Remaining`: Number of requests remaining in the current minute
- `Retry-After`: (On a 429 response) Seconds to wait before making another request

### HTTP Security Headers

To protect clients and prevent web deception attacks (like clickjacking, MIME-sniffing, or stale cache sniffing), the following HTTP headers are injected into **every** API response:

| Header | Value | Purpose |
|---|---|---|
| `X-Content-Type-Options` | `nosniff` | Prevents the browser from misinterpreting the content type. |
| `X-Frame-Options` | `DENY` | Prevents clickjacking by blocking the API from being embedded in an iframe. |
| `X-XSS-Protection` | `1; mode=block` | Enables strict cross-site scripting (XSS) filtering. |
| `Referrer-Policy` | `no-referrer` | Ensures no referrer information is leaked to third parties. |
| `Content-Security-Policy` | `default-src 'none'; script-src 'none'; object-src 'none'; base-uri 'none'; frame-ancestors 'none';` | Advanced CSP restricting all active content execution. Explicitly setting `script-src 'none'` guarantees that even if a malicious SVG file is retrieved and rendered, it cannot execute embedded JavaScript. |
| `Cache-Control` | `no-store, max-age=0, must-revalidate` | Dynamic caching policy ensuring sensitive financial data is never cached by proxies or browsers. |
