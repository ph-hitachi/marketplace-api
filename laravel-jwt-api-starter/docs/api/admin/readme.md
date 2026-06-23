# Admin API — Reference

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

### 1. List All Users

Returns a paginated list of all users in the system, newest first.

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
      "shop": null
    },
    {
      "id": 2,
      "name": "Tech Haven Store",
      "email": "seller1@marketplace.com",
      "role": "seller",
      "is_active": true,
      "balance": "0.00",
      "shop": {
        "id": 1,
        "shop_name": "Tech Haven",
        "shop_description": "Your one-stop shop for gadgets and electronics."
      }
    }
  ],
  "per_page": 20,
  "total": 2
}
```

---

### 2. Get Single User

Returns complete profile detail for a single user, including their shop (if applicable).

> **Controller:** `App\Http\Controllers\Api\Admin\UserController` | **Method:** `show`

#### `GET /api/admin/users/{id}`

**Success Response — `200 OK` (Customer):**
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
    "shop": null,
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
    "shop": {
      "shop_name": "Tech Haven",
      "shop_description": "Your one-stop shop for gadgets and electronics."
    },
    "addresses": []
  }
}
```

---

### 3. Activate User

> **Controller:** `App\Http\Controllers\Api\Admin\UserController` | **Method:** `activate`

#### `PATCH /api/admin/users/{id}/activate`

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

---

### 4. Deactivate User

> **Controller:** `App\Http\Controllers\Api\Admin\UserController` | **Method:** `deactivate`

#### `PATCH /api/admin/users/{id}/deactivate`

**Success Response — `200 OK`:**
```json
{
  "message": "User deactivated and all tokens revoked."
}
```

---

### 5. Delete User

> **Controller:** `App\Http\Controllers\Api\Admin\UserController` | **Method:** `destroy`

#### `DELETE /api/admin/users/{id}`

**Success Response — `200 OK`:**
```json
{
  "message": "User deleted."
}
```

---

## ORDER MANAGEMENT

### 6. List All Orders

Returns all orders in the system across all customers and sellers.

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
      "shop_id": 3,
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
      "shop": {
        "id": 3,
        "shop_name": "Fashion Hub",
        "shop_description": "Clothing and trends."
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
  "total": 1
}
```

---

### 7. Get Single Order

> **Controller:** `App\Http\Controllers\Api\Admin\OrderController` | **Method:** `show`

#### `GET /api/admin/orders/{id}`

**Success Response — `200 OK`:**
```json
{
  "order": {
    "id": 1,
    "status": "pending",
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
    "shop": {
      "id": 2,
      "shop_name": "Tech Haven",
      "shop_description": "Your one-stop shop for gadgets and electronics."
    },
    "address": {
      "id": 1,
      "label": "Home",
      "phone": "09171234567",
      "address_line1": "123 Rizal Street",
      "city": "Makati"
    },
    "items": [
      {
        "id": 1,
        "product_id": 1,
        "product_name": "Wireless Earbuds Pro",
        "unit_price": "1299.00",
        "quantity": 2,
        "subtotal": "2598.00"
      }
    ]
  }
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
