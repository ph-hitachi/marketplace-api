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
    │   │   ├── ProductsTest.php
    │   │   └── ShopsTest.php
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

Route::get('/shops',         [ShopController::class, 'index']);
Route::get('/shops/{shop}',  [ShopController::class, 'show']);

// ── Authenticated routes (any role) ────────────────────────────────────────
Route::middleware(['auth:api', 'active'])->group(function () {

    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);

    // ── Wallets & Profile (Shared Customer/Seller) ─────────────────────────
    Route::middleware('role:customer,seller')->prefix('user')->group(function () {
        Route::get('/me',                       [AuthController::class, 'me']);
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

        // Carts (Bookmark)
        Route::get('/carts',                    [CartController::class, 'index']);
        Route::post('/carts',                   [CartController::class, 'store']);
        Route::delete('/carts/{cart}',          [CartController::class, 'destroy']);

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

## 3. Endpoints Summary

### Public Routes
| Method | Endpoint | Success Status | Description |
|---|---|---|---|
| POST | `/api/auth/register` | `201 Created` | Register a new customer or seller account. |
| POST | `/api/auth/login` | `200 OK` | Authenticate a user and receive a token. |
| GET | `/api/products` | `200 OK` | List all active products. |
| GET | `/api/products/{id}` | `200 OK` | Retrieve details for an active product. |
| GET | `/api/shops` | `200 OK` | List all available shops. |
| GET | `/api/shops/{shop}` | `200 OK` | Retrieve a specific shop profile and active products. |

### Authentication
| Method | Endpoint | Success Status | Description |
|---|---|---|---|
| POST | `/api/auth/logout` | `200 OK` | Log out and revoke token. |
| POST | `/api/auth/refresh` | `200 OK` | Exchange token. |
| GET | `/api/user/me` | `200 OK` | Retrieve authenticated user profile. |

### User (Shared)
| Method | Endpoint | Success Status | Description |
|---|---|---|---|
| GET | `/api/user/wallets` | `200 OK` | List user wallets. |
| POST | `/api/user/wallets` | `201 Created` | Create new wallet. |
| GET | `/api/user/wallets/{wallet}` | `200 OK` | View specific wallet details. |
| POST | `/api/user/wallets/{wallet}/topup`| `200 OK` | Add funds. |
| POST | `/api/user/wallets/{wallet}/default`| `200 OK` | Set default payment wallet. |
| PUT | `/api/user/profile` | `200 OK` | Update profile details. |

### Customer Routes
| Method | Endpoint | Success Status | Description |
|---|---|---|---|
| GET | `/api/customer/addresses` | `200 OK` | List saved addresses. |
| POST | `/api/customer/addresses` | `201 Created` | Add a new address. |
| GET | `/api/customer/addresses/{address}`| `200 OK` | View a delivery address. |
| PUT | `/api/customer/addresses/{address}`| `200 OK` | Update address. |
| DELETE| `/api/customer/addresses/{address}`| `204 No Content` | Delete address. |
| PATCH | `/api/customer/addresses/{address}/default`| `200 OK` | Set default address. |
| POST | `/api/customer/orders` | `201 Created` | Place order. |
| GET | `/api/customer/orders` | `200 OK` | List orders. |
| GET | `/api/customer/orders/{order}` | `200 OK` | View order details. |
| PATCH | `/api/customer/orders/{order}/cancel`| `200 OK` | Cancel order. |
| POST | `/api/customer/orders/{order}/confirm`| `200 OK` | Confirm receipt to release funds. |

### Seller Routes
| Method | Endpoint | Success Status | Description |
|---|---|---|---|
| PUT | `/api/seller/profile` | `200 OK` | Update shop profile. |
| GET | `/api/seller/products` | `200 OK` | List seller shop products. |
| POST | `/api/seller/products` | `201 Created` | Add new product. |
| GET | `/api/seller/products/{product}` | `200 OK` | View specific product. |
| PUT | `/api/seller/products/{product}` | `200 OK` | Update product details. |
| DELETE| `/api/seller/products/{product}` | `204 No Content` | Soft-delete product. |
| PATCH | `/api/seller/products/{product}/activate`| `204 No Content` | Mark active. |
| PATCH | `/api/seller/products/{product}/deactivate`|`204 No Content` | Mark inactive. |
| GET | `/api/seller/orders` | `200 OK` | List seller shop orders. |
| GET | `/api/seller/orders/{order}` | `200 OK` | View specific seller order. |
| PATCH | `/api/seller/orders/{order}/status`| `200 OK` | Advance status. |
| PATCH | `/api/seller/orders/{order}/cancel`| `200 OK` | Cancel order. |

### Admin Routes
| Method | Endpoint | Success Status | Description |
|---|---|---|---|
| GET | `/api/admin/users` | `200 OK` | List registered users. |
| GET | `/api/admin/users/{user}` | `200 OK` | View specific user. |
| PATCH | `/api/admin/users/{user}/activate`| `204 No Content` | Reactivate user. |
| PATCH | `/api/admin/users/{user}/deactivate`| `204 No Content` | Deactivate user. |
| DELETE| `/api/admin/users/{user}` | `204 No Content` | Delete user account. |
| GET | `/api/admin/orders` | `200 OK` | List all orders. |
| GET | `/api/admin/orders/{order}` | `200 OK` | View any order details. |
