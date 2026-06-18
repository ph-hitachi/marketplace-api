# Seller API — Reference

> **Base URL:** `http://localhost/api/seller`
> **Auth Required:** ✅ Bearer Token (`role = seller`)
> **Rate Limit:** 60 requests/minute per token

All requests must include:
```
Authorization: Bearer <your_token>
Accept: application/json
```

> Get your token: `POST /api/auth/login` with a seller account.
> Demo Seller 1: `seller1@marketplace.com` / `Seller1234!` → shop: **Tech Haven**
> Demo Seller 2: `seller2@marketplace.com` / `Seller1234!` → shop: **Fashion Hub**

---

## Table of Contents

### Products
1. [List My Products](#1-list-my-products)
2. [Create Product](#2-create-product)
3. [Get Single Product](#3-get-single-product)
4. [Update Product](#4-update-product)
5. [Delete Product (Soft)](#5-delete-product-soft)
6. [Activate Product](#6-activate-product)
7. [Deactivate Product](#7-deactivate-product)

### Orders
8. [List My Orders](#8-list-my-orders)
9. [Get Single Order](#9-get-single-order)
10. [Update Order Status](#10-update-order-status)
11. [Cancel Order](#11-cancel-order)

### Seller Profile
12. [Update Seller Profile](#12-update-seller-profile)


---

## PRODUCTS

### 1. List My Products

Returns all products belonging to the authenticated seller's shop — including inactive and soft-deleted entries. Paginated at 15 per page.

> **Controller:** `App\Http\Controllers\Api\Seller\ProductController` | **Method:** `index`

#### `GET /api/seller/products`

**Success Response — `200 OK`:**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "shop_id": 2,
      "name": "Wireless Earbuds Pro",
      "slug": "wireless-earbuds-pro",
      "description": "High-quality Wireless Earbuds Pro.",
      "price": "1299.00",
      "stock": 48,
      "is_active": true,
      "deleted_at": null,
      "created_at": "2026-06-14T05:42:00.000000Z",
      "updated_at": "2026-06-14T05:42:00.000000Z"
    },
    {
      "id": 5,
      "shop_id": 2,
      "name": "Webcam 1080p HD",
      "slug": "webcam-1080p-hd",
      "price": "1599.00",
      "stock": 0,
      "is_active": false,
      "deleted_at": "2026-06-14T07:00:00.000000Z"
    }
  ],
  "per_page": 15,
  "total": 2
}
```

---

### 2. Create Product

Creates a new product listing under the authenticated seller's shop. The `slug` is **auto-generated** from the product name.

> **Controller:** `App\Http\Controllers\Api\Seller\ProductController` | **Method:** `store`

#### `POST /api/seller/products`

**Request Body:**
```json
{
  "name": "Noise Cancelling Headphones",
  "description": "Premium over-ear headphones with active noise cancellation.",
  "price": 3499.00,
  "stock": 25,
  "is_active": true
}
```

**Success Response — `201 Created`:**
```json
{
  "product": {
    "id": 11,
    "shop_id": 2,
    "name": "Noise Cancelling Headphones",
    "slug": "noise-cancelling-headphones",
    "description": "Premium over-ear headphones with active noise cancellation.",
    "price": "3499.00",
    "stock": 25,
    "is_active": true,
    "deleted_at": null,
    "created_at": "2026-06-14T07:00:00.000000Z",
    "updated_at": "2026-06-14T07:00:00.000000Z"
  }
}
```

**Validation Rules:**
| Field | Required | Rule |
|---|---|---|
| `name` | ✅ | String, max 255 chars |
| `description` | ❌ | String, max 5000 chars |
| `price` | ✅ | Numeric, min 0.01 |
| `stock` | ✅ | Integer, min 0 |
| `is_active` | ❌ | Boolean (defaults to `true`) |

---

### 3. Get Single Product

Returns details of one of your products.

> **Controller:** `App\Http\Controllers\Api\Seller\ProductController` | **Method:** `show`

#### `GET /api/seller/products/{id}`

**Success Response — `200 OK`:**
```json
{
  "product": {
    "id": 1,
    "shop_id": 2,
    "name": "Wireless Earbuds Pro",
    "slug": "wireless-earbuds-pro",
    "description": "High-quality Wireless Earbuds Pro.",
    "price": "1299.00",
    "stock": 48,
    "is_active": true,
    "deleted_at": null
  }
}
```

---

### 4. Update Product

Updates one or more fields of an existing product.

> **Controller:** `App\Http\Controllers\Api\Seller\ProductController` | **Method:** `update`

#### `PUT /api/seller/products/{id}`

**Request Body:**
```json
{
  "price": 999.00,
  "stock": 100
}
```

**Success Response — `200 OK`:**
```json
{
  "product": {
    "id": 1,
    "shop_id": 2,
    "name": "Wireless Earbuds Pro",
    "price": "999.00",
    "stock": 100,
    "is_active": true,
    "updated_at": "2026-06-14T07:15:00.000000Z"
  }
}
```

---

### 5. Delete Product (Soft)

Soft-deletes a product. If the product has orders, deletion is blocked (deactivate instead).

> **Controller:** `App\Http\Controllers\Api\Seller\ProductController` | **Method:** `destroy`

#### `DELETE /api/seller/products/{id}`

**Success Response — `204 No Content`**

---

### 6. Activate Product

> **Controller:** `App\Http\Controllers\Api\Seller\ProductController` | **Method:** `activate`

#### `PATCH /api/seller/products/{id}/activate`

**Success Response — `204 No Content`**

---

### 7. Deactivate Product

> **Controller:** `App\Http\Controllers\Api\Seller\ProductController` | **Method:** `deactivate`

#### `PATCH /api/seller/products/{id}/deactivate`

**Success Response — `204 No Content`**

---

## ORDERS

### 8. List My Orders

Returns all orders assigned to the authenticated seller's shop, newest first.

> **Controller:** `App\Http\Controllers\Api\Seller\OrderController` | **Method:** `index`

#### `GET /api/seller/orders`

**Success Response — `200 OK`:**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "customer_id": 4,
      "shop_id": 2,
      "address_id": 1,
      "status": "pending",
      "total_amount": "3397.00",
      "batch_ref": "550e8400-e29b-41d4-a716-446655440000",
      "created_at": "2026-06-14T06:20:00.000000Z",
      "items": [
        {
          "id": 1,
          "product_id": 1,
          "product_name": "Wireless Earbuds Pro",
          "unit_price": "1299.00",
          "quantity": 2,
          "subtotal": "2598.00"
        }
      ],
      "address": {
        "label": "Home",
        "address_line1": "123 Rizal Street",
        "city": "Makati",
        "province": "Metro Manila",
        "postal_code": "1200",
        "country": "Philippines"
      }
    }
  ],
  "per_page": 15,
  "total": 1
}
```

---

### 9. Get Single Order

> **Controller:** `App\Http\Controllers\Api\Seller\OrderController` | **Method:** `show`

#### `GET /api/seller/orders/{id}`

**Success Response — `200 OK`:**
```json
{
  "order": {
    "id": 1,
    "shop_id": 2,
    "status": "pending",
    "total_amount": "3397.00",
    "batch_ref": "550e8400-e29b-41d4-a716-446655440000",
    "items": [
      {
        "id": 1,
        "product_id": 1,
        "product_name": "Wireless Earbuds Pro",
        "unit_price": "1299.00",
        "quantity": 2,
        "subtotal": "2598.00"
      }
    ],
    "address": {
      "label": "Home",
      "address_line1": "123 Rizal Street",
      "city": "Makati",
      "province": "Metro Manila",
      "postal_code": "1200",
      "country": "Philippines"
    }
  }
}
```

---

### 10. Update Order Status

Advances the status of a pending order. Valid transitions: `pending -> shipped -> delivered`.

> **Controller:** `App\Http\Controllers\Api\Seller\OrderController` | **Method:** `updateStatus`

#### `PATCH /api/seller/orders/{id}/status`

**Request Body:**
```json
{
  "status": "shipped"
}
```

**Success Response — `200 OK`:**
```json
{
  "message": "Order status updated.",
  "order": {
    "id": 1,
    "status": "shipped",
    "total_amount": "3397.00",
    "updated_at": "2026-06-14T07:30:00.000000Z"
  }
}
```

---

### 11. Cancel Order

> **Controller:** `App\Http\Controllers\Api\Seller\OrderController` | **Method:** `cancel`

#### `PATCH /api/seller/orders/{id}/cancel`

**Request Body:**
```json
{
  "cancel_reason": 5,
  "cancel_reason_notes": "Out of stock / mistake in inventory"
}
```

**Success Response — `200 OK`:**
```json
{
  "message": "Order cancelled and refund issued.",
  "order": {
    "id": 1,
    "status": "cancelled",
    "total_amount": "3397.00",
    "cancel_reason": 5,
    "cancel_reason_notes": "Out of stock / mistake in inventory",
    "cancel_at": "2026-06-14T08:00:00.000000Z"
  }
}
```

---

## SELLER PROFILE

### 12. Update Seller Profile

Updates the shop profile of the seller.

> **Controller:** `App\Http\Controllers\Api\Seller\ShopProfileController` | **Method:** `update`

#### `PUT /api/seller/profile`

**Request Body:**
```json
{
  "shop_name": "Tech Haven Premium",
  "shop_description": "We offer the absolute best gadgets and audio devices."
}
```

**Success Response — `200 OK`:**
```json
{
  "message": "Seller profile updated successfully.",
  "profile": {
    "id": 1,
    "shop_name": "Tech Haven Premium",
    "shop_description": "We offer the absolute best gadgets and audio devices.",
    "created_at": "2026-06-14T05:42:00.000000Z",
    "updated_at": "2026-06-14T07:45:00.000000Z"
  }
}
```

**Validation Rules:**
| Field | Required | Rule |
|---|---|---|
| `shop_name` | ✅ | String, max 255 chars, unique in `shops` table (ignoring current shop) |
| `shop_description` | ❌ | String, max 1000 chars |

---

## Common Error Responses

All errors return a consistent JSON shape:
```json
{
  "error_code": "SNAKE_CASE_CODE",
  "message": "Human-readable description."
}
```
