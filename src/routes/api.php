<?php

use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Customer\AddressController;
use App\Http\Controllers\Api\Customer\CartController;
use App\Http\Controllers\Api\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Api\User\WalletController;
use App\Http\Controllers\Api\User\ProfileController as UserProfileController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\Seller\OrderController as SellerOrderController;
use App\Http\Controllers\Api\Seller\ProductController as SellerProductController;
use App\Http\Controllers\Api\Seller\SellerProfileController;
use App\Http\Controllers\Api\Seller\ShopController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Marketplace API Routes
|--------------------------------------------------------------------------
|
| All routes are under /api (prefix applied by bootstrap/app.php).
| Rate limiting: throttle:60,1 applied globally via the api middleware group.
|
*/

// ── Public routes (no auth) ────────────────────────────────────────────────
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login',    [AuthController::class, 'login']);

Route::get('/products',      [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

Route::get('/shops',                 [ShopController::class, 'index']);
Route::get('/shops/{shop}',          [ShopController::class, 'show']);

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

    // ── Seller Profile, Products, & Orders fulfillment ──────────────────────
    Route::middleware('role:seller')->prefix('seller')->group(function () {
        Route::put('/profile',             [SellerProfileController::class, 'update']);

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
