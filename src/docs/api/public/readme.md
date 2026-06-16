# Public API — Postman Reference

> **Base URL:** `http://localhost/api`
> **Auth Required:** ❌ None
> **Rate Limit:** 60 requests/minute per IP

These endpoints are open to anyone — no token needed. Use them to browse products and authenticate.

---

## Table of Contents
1. [Register](#1-register)
2. [Login](#2-login)
3. [Browse Products](#3-browse-products)
4. [Get Single Product](#4-get-single-product)
5. [Logout](#5-logout-any-authenticated-user)
6. [Get Own Profile](#6-get-own-profile-any-authenticated-user)

---

## 1. Register

Creates a new user account. Role can be `customer` or `seller`. **Admins cannot self-register.**

> **Controller:** `App\Http\Controllers\Api\AuthController` | **Method:** `register`

### `POST /api/auth/register`

#### Headers
| Key | Value |
|---|---|
| `Content-Type` | `application/json` |
| `Accept` | `application/json` |

#### Register as Customer

**Request Body:**
```json
{
  "name": "Juan dela Cruz",
  "email": "juan@example.com",
  "password": "Secret1234",
  "password_confirmation": "Secret1234",
  "role": "customer"
}
```

**Success Response — `201 Created`:**
```json
{
  "message": "Registration successful.",
  "user": {
    "id": 6,
    "name": "Juan dela Cruz",
    "email": "juan@example.com",
    "role": "customer",
    "is_active": true,
    "balance": "0.00",
    "seller_profile": null,
    "created_at": "2026-06-14T06:00:00.000000Z",
    "updated_at": "2026-06-14T06:00:00.000000Z"
  },
  "access_token": "1|abc123tokenxyz...",
  "token_type": "Bearer"
}
```

> ⚠️ **Save the `access_token`** — you'll need it as the Bearer token for all protected customer endpoints. It expires in **3 days**.

---

#### Register as Seller

`shop_name` is **required** for sellers and must be unique. `shop_description` is optional.

**Request Body:**
```json
{
  "name": "My Store Owner",
  "email": "mystore@example.com",
  "password": "Secret1234",
  "password_confirmation": "Secret1234",
  "role": "seller",
  "shop_name": "My Awesome Store",
  "shop_description": "We sell premium handcrafted goods."
}
```

**Success Response — `201 Created`:**
```json
{
  "message": "Registration successful.",
  "user": {
    "id": 7,
    "name": "My Store Owner",
    "email": "mystore@example.com",
    "role": "seller",
    "is_active": true,
    "balance": "0.00",
    "seller_profile": {
      "id": 3,
      "user_id": 7,
      "shop_name": "My Awesome Store",
      "shop_description": "We sell premium handcrafted goods."
    }
  },
  "access_token": "2|def456tokenabc...",
  "token_type": "Bearer"
}
```

**Validation Error — `422 Unprocessable Entity`:**
```json
{
  "error_code": "VALIDATION_ERROR",
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."],
    "shop_name": ["The shop name has already been taken."]
  }
}
```

**Validation Rules:**
| Field | Rule |
|---|---|
| `name` | Required, max 255 chars |
| `email` | Required, valid email, unique |
| `password` | Required, min 8 chars, must have letters + numbers, confirmed |
| `role` | Required, must be `customer` or `seller` |
| `shop_name` | Required when `role=seller`, max 255, unique |
| `shop_description` | Optional, max 1000 chars |

---

## 2. Login

Authenticates an existing user and returns a 3-day Bearer token.

> **Controller:** `App\Http\Controllers\Api\AuthController` | **Method:** `login`

### `POST /api/auth/login`

#### Headers
| Key | Value |
|---|---|
| `Content-Type` | `application/json` |
| `Accept` | `application/json` |

**Request Body:**
```json
{
  "email": "customer1@marketplace.com",
  "password": "Customer1234!"
}
```

**Success Response — `200 OK`:**
```json
{
  "message": "Login successful.",
  "user": {
    "id": 4,
    "name": "Juan dela Cruz",
    "email": "customer1@marketplace.com",
    "role": "customer",
    "is_active": true,
    "balance": "5000.00"
  },
  "access_token": "3|ghiTokenValue...",
  "token_type": "Bearer"
}
```

> **Note:** Each login revokes all previous tokens for this user. Only one active session is maintained per account.

**Error — Invalid Credentials `401`:**
```json
{
  "error_code": "UNAUTHENTICATED",
  "message": "Invalid credentials."
}
```

**Error — Deactivated Account `403`:**
```json
{
  "error_code": "ACCOUNT_DEACTIVATED",
  "message": "Your account has been deactivated. Please contact support."
}
```

### Seeded Demo Accounts
| Role | Email | Password |
|---|---|---|
| Admin | `admin@marketplace.com` | `Admin1234!` |
| Seller 1 | `seller1@marketplace.com` | `Seller1234!` |
| Seller 2 | `seller2@marketplace.com` | `Seller1234!` |
| Customer 1 | `customer1@marketplace.com` | `Customer1234!` |
| Customer 2 | `customer2@marketplace.com` | `Customer1234!` |

---

## 3. Browse Products

Returns a paginated list of all active, in-stock products available for purchase.

> **Controller:** `App\Http\Controllers\Api\ProductController` | **Method:** `index`

### `GET /api/products`

#### Headers
| Key | Value |
|---|---|
| `Accept` | `application/json` |

**Success Response — `200 OK`:**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "seller_id": 2,
      "name": "Wireless Earbuds Pro",
      "slug": "wireless-earbuds-pro",
      "description": "High-quality Wireless Earbuds Pro from Tech Haven.",
      "price": "1299.00",
      "stock": 50,
      "tags": ["electronics", "audio", "wireless"],
      "is_active": true,
      "deleted_at": null,
      "created_at": "2026-06-14T05:42:00.000000Z",
      "updated_at": "2026-06-14T05:42:00.000000Z",
      "seller": {
        "id": 2,
        "name": "Tech Haven Store",
        "seller_profile": {
          "shop_name": "Tech Haven",
          "shop_description": "Your one-stop shop for gadgets and electronics."
        }
      }
    }
  ],
  "first_page_url": "http://localhost/api/products?page=1",
  "per_page": 15,
  "to": 10,
  "total": 10
}
```

> **Pagination:** Use `?page=2` to get the next page.
> **Note:** Products with `stock = 0` or `is_active = false` are **excluded** from public listing.

---

## 4. Get Single Product

Returns the details of a single active product.

> **Controller:** `App\Http\Controllers\Api\ProductController` | **Method:** `show`

### `GET /api/products/{id}`

**Example:** `GET /api/products/1`

**Success Response — `200 OK`:**
```json
{
  "product": {
    "id": 1,
    "seller_id": 2,
    "name": "Wireless Earbuds Pro",
    "slug": "wireless-earbuds-pro",
    "description": "High-quality Wireless Earbuds Pro from Tech Haven.",
    "price": "1299.00",
    "stock": 50,
    "tags": ["electronics", "audio", "wireless"],
    "is_active": true,
    "seller": {
      "id": 2,
      "name": "Tech Haven Store",
      "seller_profile": {
        "shop_name": "Tech Haven",
        "shop_description": "Your one-stop shop for gadgets and electronics."
      }
    }
  }
}
```

**Error — Not Found `404`:**
```json
{
  "error_code": "NOT_FOUND",
  "message": "The requested resource was not found."
}
```

---

## 5. Logout (Any Authenticated User)

Revokes the current Bearer token. Subsequent requests using that token return 401.

> **Controller:** `App\Http\Controllers\Api\AuthController` | **Method:** `logout`

### `POST /api/auth/logout`

#### Headers
| Key | Value |
|---|---|
| `Authorization` | `Bearer <your_token>` |
| `Accept` | `application/json` |

**Success Response — `200 OK`:**
```json
{
  "message": "Logged out successfully."
}
```

---

## 6. Get Own Profile (Any Authenticated User)

Returns the authenticated user's profile. Includes `seller_profile` for sellers and `balance` for customers.

> **Controller:** `App\Http\Controllers\Api\AuthController` | **Method:** `me`

### `GET /api/auth/me`

#### Headers
| Key | Value |
|---|---|
| `Authorization` | `Bearer <your_token>` |
| `Accept` | `application/json` |

**Success Response — `200 OK` (Customer):**
```json
{
  "user": {
    "id": 4,
    "name": "Juan dela Cruz",
    "email": "customer1@marketplace.com",
    "role": "customer",
    "is_active": true,
    "balance": "5000.00",
    "seller_profile": null
  }
}
```

**Success Response — `200 OK` (Seller):**
```json
{
  "user": {
    "id": 2,
    "name": "Tech Haven Store",
    "email": "seller1@marketplace.com",
    "role": "seller",
    "is_active": true,
    "balance": "0.00",
    "seller_profile": {
      "shop_name": "Tech Haven",
      "shop_description": "Your one-stop shop for gadgets and electronics."
    }
  }
}
```

---

## Common Error Responses

All errors follow a consistent JSON shape:
```json
{
  "error_code": "SNAKE_CASE_CODE",
  "message": "Human-readable description."
}
```
Validation errors additionally include an `errors` object with field-level detail.

| HTTP Code | Meaning | Exception Class | When it happens |
|---|---|---|---|
| `401` | Unauthenticated | `Illuminate\Auth\AuthenticationException` | No Bearer token, invalid token, or expired token |
| `403` | Forbidden | `Illuminate\Auth\Access\AuthorizationException` | Valid token but wrong role, or policy check failed |
| `403` | Account Deactivated | `App\Exceptions\AccountDeactivatedException` | Token is valid but user's `is_active = false`; token is also immediately revoked |
| `404` | Not Found | `Symfony\Component\HttpKernel\Exception\NotFoundHttpException` | Route or model does not exist |
| `422` | Validation Error | `Illuminate\Validation\ValidationException` | Missing or invalid request fields |
| `429` | Too Many Requests | `Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException` | Exceeded 60 requests/minute |
| `500` | Server Error | `Throwable` (catch-all) | Unexpected server-side failure; error is logged, safe message returned |

**Example 500 response:**
```json
{
  "error_code": "SERVER_ERROR",
  "message": "Sorry, something went wrong on the server. Please try again later."
}
```
