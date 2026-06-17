# Admin API — Postman Reference

> **Base URL:** `http://localhost/api/admin`
> **Auth Required:** ✅ Bearer Token (`role = admin`)
> **Rate Limit:** 60 requests/minute per token

All requests must include:
```
Authorization: Bearer <your_token>
Accept: application/json
```

> Get your admin token: `POST /api/auth/login`
> Admin account: `admin@marketplace.com` / `Admin1234!`

---

## Table of Contents

### User Management
1. [List All Users](#1-list-all-users)
2. [Get Single User](#2-get-single-user)
3. [Activate User](#3-activate-user)
4. [Deactivate User](#4-deactivate-user)
5. [Delete User](#5-delete-user)

### Order Management
6. [List All Orders](#6-list-all-orders)
7. [Get Single Order](#7-get-single-order)

---

## USER MANAGEMENT

Admins have full visibility and control over all user accounts regardless of role. This is the primary access control surface for the security demo.

### 1. List All Users

Returns a paginated list of all users in the system (customers, sellers, admins), newest first.

> **Controller:** `App\Http\Controllers\Api\Admin\UserController` | **Method:** `index`

#### `GET /api/admin/users`

**Success Response — `200 OK`:**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "name": "Admin",
      "email": "admin@marketplace.com",
      "role": "admin",
      "is_active": true,
      "balance": "0.00",
      "email_verified_at": null,
      "created_at": "2026-06-14T05:42:00.000000Z",
      "updated_at": "2026-06-14T05:42:00.000000Z",
      "seller_profile": null
    },
    {
      "id": 2,
      "name": "Tech Haven Store",
      "email": "seller1@marketplace.com",
      "role": "seller",
      "is_active": true,
      "balance": "0.00",
      "seller_profile": {
        "id": 1,
        "user_id": 2,
        "shop_name": "Tech Haven",
        "shop_description": "Your one-stop shop for gadgets and electronics."
      }
    },
    {
      "id": 4,
      "name": "Juan dela Cruz",
      "email": "customer1@marketplace.com",
      "role": "customer",
      "is_active": true,
      "balance": "3453.00",
      "seller_profile": null
    }
  ],
  "per_page": 20,
  "total": 5
}
```

> Use `?page=2` to paginate through users.

---

### 2. Get Single User

Returns complete profile detail for a single user, including their seller profile (if applicable) and saved addresses.

> **Controller:** `App\Http\Controllers\Api\Admin\UserController` | **Method:** `show`

#### `GET /api/admin/users/{id}`

**Example:** `GET /api/admin/users/4`

**Success Response — `200 OK`:**
```json
{
  "user": {
    "id": 4,
    "name": "Juan dela Cruz",
    "email": "customer1@marketplace.com",
    "role": "customer",
    "is_active": true,
    "balance": "3453.00",
    "email_verified_at": null,
    "created_at": "2026-06-14T05:42:00.000000Z",
    "updated_at": "2026-06-14T07:25:00.000000Z",
    "seller_profile": null,
    "addresses": [
      {
        "id": 1,
        "label": "Home",
        "phone": "09171234567",
        "address_line1": "123 Rizal Street",
        "city": "Makati",
        "province": "Metro Manila",
        "postal_code": "1200",
        "country": "Philippines",
        "is_default": true
      }
    ]
  }
}
```

**Example for a seller:** `GET /api/admin/users/2`
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
    },
    "addresses": []
  }
}
```

---

### 3. Activate User

Sets a user's `is_active` flag to `true`, restoring their ability to log in and use the API.

> **Controller:** `App\Http\Controllers\Api\Admin\UserController` | **Method:** `activate`

#### `PATCH /api/admin/users/{id}/activate`

**Example:** `PATCH /api/admin/users/4/activate`

No request body needed.

**Success Response — `200 OK`:**
```json
{
  "message": "User activated.",
  "user": {
    "id": 4,
    "name": "Juan dela Cruz",
    "email": "customer1@marketplace.com",
    "is_active": true
  }
}
```

> After activation the user can log in normally. Their old tokens have already been revoked (from deactivation), so they must log in again to get a fresh token.

---

### 4. Deactivate User

Sets `is_active = false` and **immediately deletes all of the user's active tokens**. Any subsequent request made with their token will return `403 Account deactivated`.

> **Controller:** `App\Http\Controllers\Api\Admin\UserController` | **Method:** `deactivate`

#### `PATCH /api/admin/users/{id}/deactivate`

**Example:** `PATCH /api/admin/users/4/deactivate`

No request body needed.

**Success Response — `200 OK`:**
```json
{
  "message": "User deactivated and all tokens revoked."
}
```

**What happens internally:**
1. `users.is_active` is set to `false`
2. All rows in `personal_access_tokens` for this user are deleted
3. Any in-flight request using their token hits the `EnsureUserIsActive` middleware and receives a `403`

**Security Demo Flow:**
1. Login as `customer1@marketplace.com` → copy the token
2. Admin calls `PATCH /api/admin/users/4/deactivate`
3. Use the copied customer token on any endpoint → expect `403`:
```json
{
  "error_code": "ACCOUNT_DEACTIVATED",
  "message": "Your account has been deactivated. Please contact support."
}
```
4. Admin calls `PATCH /api/admin/users/4/activate`
5. Customer logs in again → gets fresh token → works normally

---

### 5. Delete User

**Permanently hard-deletes** a user from the database. This is irreversible.

> **Controller:** `App\Http\Controllers\Api\Admin\UserController` | **Method:** `destroy`

#### `DELETE /api/admin/users/{id}`

**Example:** `DELETE /api/admin/users/6`

No request body needed.

**Success Response — `200 OK`:**
```json
{
  "message": "User deleted."
}
```

**Blocked if user has non-cancelled orders `422`:**
```json
{
  "error_code": "DELETE_BLOCKED",
  "message": "Cannot delete user with active or non-cancelled orders."
}
```

> This safety check prevents orphaning order history. You must cancel all of the user's orders first before deletion is allowed. The check applies to the user as a customer (orders they placed).

**Before deleting:**
- All of the user's active tokens are revoked
- Their DB record and related data (seller profile, addresses) is deleted via cascade

---

## ORDER MANAGEMENT

Admins have full read access to all orders across all customers and sellers. Admin can override any order's status without restriction.

### 6. List All Orders

Returns all orders in the system across all customers and sellers, paginated 20 per page, newest first.

> **Controller:** `App\Http\Controllers\Api\Admin\OrderController` | **Method:** `index`

#### `GET /api/admin/orders`

**Success Response — `200 OK`:**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 2,
      "customer_id": 4,
      "seller_id": 3,
      "address_id": 1,
      "status": "shipped",
      "total_amount": "1850.00",
      "batch_ref": "550e8400-e29b-41d4-a716-446655440000",
      "created_at": "2026-06-14T06:20:00.000000Z",
      "updated_at": "2026-06-14T07:30:00.000000Z",
      "customer": {
        "id": 4,
        "name": "Juan dela Cruz",
        "email": "customer1@marketplace.com"
      },
      "seller": {
        "id": 3,
        "name": "Fashion Hub Store",
        "seller_profile": { "shop_name": "Fashion Hub" }
      },
      "address": {
        "label": "Home",
        "address_line1": "123 Rizal Street",
        "city": "Makati"
      },
      "items": [
        {
          "product_name": "Classic White Sneakers",
          "unit_price": "1850.00",
          "quantity": 1,
          "subtotal": "1850.00"
        }
      ]
    }
  ],
  "per_page": 20,
  "total": 2
}
```

> **Tip:** Use `batch_ref` to identify all orders that were part of the same customer batch submission. Orders in a batch all share the same `batch_ref` UUID.

---

### 7. Get Single Order

Returns complete information for any order — customer details, seller details, all items, and delivery address.

> **Controller:** `App\Http\Controllers\Api\Admin\OrderController` | **Method:** `show`

#### `GET /api/admin/orders/{id}`

**Example:** `GET /api/admin/orders/1`

**Success Response — `200 OK`:**
```json
{
  "order": {
    "id": 1,
    "status": "processing",
    "total_amount": "3397.00",
    "batch_ref": "550e8400-e29b-41d4-a716-446655440000",
    "created_at": "2026-06-14T06:20:00.000000Z",
    "updated_at": "2026-06-14T07:30:00.000000Z",
    "customer": {
      "id": 4,
      "name": "Juan dela Cruz",
      "email": "customer1@marketplace.com",
      "role": "customer",
      "balance": "3453.00"
    },
    "seller": {
      "id": 2,
      "name": "Tech Haven Store",
      "seller_profile": {
        "shop_name": "Tech Haven",
        "shop_description": "Your one-stop shop for gadgets and electronics."
      }
    },
    "address": {
      "id": 1,
      "label": "Home",
      "phone": "09171234567",
      "address_line1": "123 Rizal Street",
      "address_line2": null,
      "city": "Makati",
      "province": "Metro Manila",
      "postal_code": "1200",
      "country": "Philippines"
    },
    "items": [
      {
        "id": 1,
        "product_id": 1,
        "product_name": "Wireless Earbuds Pro",
        "unit_price": "1299.00",
        "quantity": 2,
        "subtotal": "2598.00",
        "product": {
          "id": 1,
          "name": "Wireless Earbuds Pro",
          "stock": 48,
          "is_active": true
        }
      },
      {
        "id": 2,
        "product_id": 2,
        "product_name": "USB-C Hub 7-in-1",
        "unit_price": "799.00",
        "quantity": 1,
        "subtotal": "799.00"
      }
    ]
  }
}
```
---

## Security Demo Scenarios

### Scenario 1: RBAC Bypass Test

Prove that role-based access control is enforced:

| Action | Token | Expected Result |
|---|---|---|
| `GET /api/admin/users` | Customer token | `403 Forbidden` |
| `GET /api/admin/users` | Seller token | `403 Forbidden` |
| `POST /api/seller/products` | Customer token | `403 Forbidden` |
| `GET /api/customer/orders` | Seller token | `403 Forbidden` |

All return:
```json
{
  "error_code": "FORBIDDEN",
  "message": "You do not have permission to perform this action."
}
```

### Scenario 2: Account Deactivation

1. Login as customer, save token A
2. Admin: `PATCH /api/admin/users/{customer_id}/deactivate`
3. Use token A on `GET /api/customer/wallet`:
```json
{
  "error_code": "ACCOUNT_DEACTIVATED",
  "message": "Your account has been deactivated. Please contact support."
}
```
4. Admin: `PATCH /api/admin/users/{customer_id}/activate`
5. Customer logs in again → receives new token → everything works

### Scenario 3: Ownership Enforcement

Prove that sellers cannot access each other's data:

1. Login as Seller 1, get order ID from Seller 2's orders
2. `GET /api/seller/orders/{seller2_order_id}` with Seller 1's token:
```json
{
  "error_code": "FORBIDDEN",
  "message": "This action is unauthorized."
}
```

### Scenario 4: Expired Token

1. Manually update `expires_at` in `personal_access_tokens` table to a past timestamp:
```sql
UPDATE personal_access_tokens SET expires_at = '2020-01-01' WHERE id = 1;
```
2. Use that token on any endpoint:
```json
{
  "error_code": "UNAUTHENTICATED",
  "message": "You are not authenticated. Please provide a valid Bearer token."
}
```

---

## Common Error Responses

All errors return a consistent JSON shape:
```json
{
  "error_code": "SNAKE_CASE_CODE",
  "message": "Human-readable description."
}
```

| HTTP Code | Meaning | Exception Class | When it happens |
|---|---|---|---|
| `401` | Unauthenticated | `Illuminate\Auth\AuthenticationException` | No Bearer token, invalid or expired token |
| `403` | Forbidden | `Illuminate\Auth\Access\AuthorizationException` | Using a non-admin token or policy check failed |
| `403` | Account Deactivated | `App\Exceptions\AccountDeactivatedException` | User's `is_active = false`; token immediately revoked |
| `404` | Not Found | `Symfony\Component\HttpKernel\Exception\NotFoundHttpException` | User or order not found |
| `422` | Validation Error | `Illuminate\Validation\ValidationException` | Missing or invalid request fields |
| `422` | Delete User Blocked | `App\Exceptions\UserDeleteBlockedException` | Trying to delete a user who has active/non-cancelled orders |
| `429` | Too Many Requests | `Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException` | Exceeded 60 requests/minute |
| `500` | Server Error | `Throwable` (catch-all) | Unexpected server failure; error is logged, safe message returned |

**Example 500 response:**
```json
{
  "error_code": "SERVER_ERROR",
  "message": "Sorry, something went wrong on the server. Please try again later."
}
```
