# Seller API — Postman Reference

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

Returns all products belonging to the authenticated seller — including inactive and (for the seller's own view) with soft-deleted entries visible. Paginated at 15 per page.

> **Controller:** `App\Http\Controllers\Api\Seller\ProductController` | **Method:** `index`

#### `GET /api/seller/products`

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
      "stock": 48,
      "tags": ["electronics", "audio", "wireless"],
      "is_active": true,
      "deleted_at": null,
      "created_at": "2026-06-14T05:42:00.000000Z",
      "updated_at": "2026-06-14T05:42:00.000000Z"
    },
    {
      "id": 5,
      "seller_id": 2,
      "name": "Webcam 1080p HD",
      "slug": "webcam-1080p-hd",
      "price": "1599.00",
      "stock": 0,
      "tags": ["electronics", "peripherals"],
      "is_active": false,
      "deleted_at": "2026-06-14T07:00:00.000000Z"
    }
  ],
  "per_page": 15,
  "total": 5
}
```

> **Note:** This shows **your products only** — including inactive and soft-deleted ones. Public customers cannot see inactive/deleted products.

---

### 2. Create Product

Creates a new product listing under the authenticated seller's account. The `slug` is **auto-generated** from the product name — you do not supply it.

> **Controller:** `App\Http\Controllers\Api\Seller\ProductController` | **Method:** `store`

#### `POST /api/seller/products`

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "name": "Noise Cancelling Headphones",
  "description": "Premium over-ear headphones with active noise cancellation, 30-hour battery life.",
  "price": 3499.00,
  "stock": 25,
  "tags": ["electronics", "audio", "wireless"],
  "is_active": true
}
```

**Success Response — `201 Created`:**
```json
{
  "product": {
    "id": 11,
    "seller_id": 2,
    "name": "Noise Cancelling Headphones",
    "slug": "noise-cancelling-headphones",
    "description": "Premium over-ear headphones with active noise cancellation, 30-hour battery life.",
    "price": "3499.00",
    "stock": 25,
    "tags": ["electronics", "audio", "wireless"],
    "is_active": true,
    "deleted_at": null,
    "created_at": "2026-06-14T07:00:00.000000Z",
    "updated_at": "2026-06-14T07:00:00.000000Z"
  }
}
```

**Auto Slug Collision Handling:**
> If a product named "Wireless Earbuds Pro" already exists, the new product slug becomes `wireless-earbuds-pro-1`, then `wireless-earbuds-pro-2`, etc.

**Validation Rules:**
| Field | Required | Rule |
|---|---|---|
| `name` | ✅ | String, max 255 chars |
| `description` | ❌ | String, max 5000 chars |
| `price` | ✅ | Numeric, min ₱0.01, max ₱9,999,999.99 |
| `stock` | ✅ | Integer, min 0 |
| `tags` | ❌ | Array of strings, max 10 tags, each max 50 chars |
| `is_active` | ❌ | Boolean (defaults to `true`) |

**Tags Examples:**
```json
"tags": ["electronics", "gadget", "wireless"]
"tags": ["fashion", "footwear", "women"]
"tags": []
```

---

### 3. Get Single Product

Returns full detail of one of your products, including soft-deleted ones.

> **Controller:** `App\Http\Controllers\Api\Seller\ProductController` | **Method:** `show`

#### `GET /api/seller/products/{id}`

**Example:** `GET /api/seller/products/1`

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
    "stock": 48,
    "tags": ["electronics", "audio", "wireless"],
    "is_active": true,
    "deleted_at": null
  }
}
```

**Error — Accessing Another Seller's Product `403`:**
```json
{
  "error_code": "FORBIDDEN",
  "message": "This action is unauthorized."
}
```

---

### 4. Update Product

Updates one or more fields of an existing product. Only include the fields you want to change.

> **Controller:** `App\Http\Controllers\Api\Seller\ProductController` | **Method:** `update`

#### `PUT /api/seller/products/{id}`

**Example:** `PUT /api/seller/products/1`

**Request Body (full update example):**
```json
{
  "name": "Wireless Earbuds Pro V2",
  "description": "Updated with better bass and longer battery.",
  "price": 1399.00,
  "stock": 75,
  "tags": ["electronics", "audio", "wireless", "bluetooth"],
  "is_active": true
}
```

**Request Body (partial update — price and stock only):**
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
    "name": "Wireless Earbuds Pro V2",
    "price": "999.00",
    "stock": 100,
    "tags": ["electronics", "audio", "wireless", "bluetooth"],
    "is_active": true,
    "updated_at": "2026-06-14T07:15:00.000000Z"
  }
}
```

> **Security:** Price changes only affect **future orders**. Existing order items have a `unit_price` snapshot that is never overwritten.

**Deactivate a product (hide from public):**
```json
{
  "is_active": false
}
```

---

### 5. Delete Product (Soft)

Soft-deletes a product — it disappears from public listing but **order history is preserved**. The product record still exists in the database with a `deleted_at` timestamp.

> **Controller:** `App\Http\Controllers\Api\Seller\ProductController` | **Method:** `destroy`

#### `DELETE /api/seller/products/{id}`

**Example:** `DELETE /api/seller/products/11`

**Success Response — `200 OK`:**
```json
{
  "message": "Product deleted."
}
```

**Error — Orders Exist `403`:**
```json
{
  "error_code": "FORBIDDEN",
  "message": "This action is unauthorized."
}
```

> You cannot delete a product that has **ever** been ordered (to preserve order history). Instead, deactivate the product using the Deactivate Product endpoint to hide the product from customers so they can no longer place new orders.

---

### 6. Activate Product

Activates a product, making it visible to customers and allowing new orders.

> **Controller:** `App\Http\Controllers\Api\Seller\ProductController` | **Method:** `activate`

#### `PATCH /api/seller/products/{id}/activate`

**Example:** `PATCH /api/seller/products/11/activate`

**Success Response — `200 OK`:**
```json
{
  "message": "Product activated successfully",
  "product": {
    "id": 11,
    "is_active": true
  }
}
```

---

### 7. Deactivate Product

Deactivates a product, hiding it from public view and preventing new orders.

> **Controller:** `App\Http\Controllers\Api\Seller\ProductController` | **Method:** `deactivate`

#### `PATCH /api/seller/products/{id}/deactivate`

**Example:** `PATCH /api/seller/products/11/deactivate`

**Success Response — `200 OK`:**
```json
{
  "message": "Product deactivated successfully",
  "product": {
    "id": 11,
    "is_active": false
  }
}
```

---

## ORDERS

Orders are automatically created when customers place purchases. As a seller, you can view only orders assigned to your shop and update their delivery status.

### 8. List My Orders

Returns all orders assigned to the authenticated seller, newest first. Includes items and delivery address.

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
      "seller_id": 2,
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
        },
        {
          "id": 2,
          "product_id": 2,
          "product_name": "USB-C Hub 7-in-1",
          "unit_price": "799.00",
          "quantity": 1,
          "subtotal": "799.00"
        }
      ],
      "customer": {
        "id": 4,
        "name": "Juan dela Cruz",
        "email": "customer1@marketplace.com"
      },
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

> **Note:** You only see orders where `seller_id` matches your user ID. Orders from the same customer batch that went to another seller are **not visible to you**.

---

### 7. Get Single Order

Returns full details of one specific order assigned to the authenticated seller.

> **Controller:** `App\Http\Controllers\Api\Seller\OrderController` | **Method:** `show`

#### `GET /api/seller/orders/{id}`

**Example:** `GET /api/seller/orders/1`

**Success Response — `200 OK`:**
```json
{
  "order": {
    "id": 1,
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
        "subtotal": "2598.00",
        "product": {
          "id": 1,
          "name": "Wireless Earbuds Pro",
          "stock": 48
        }
      }
    ],
    "customer": {
      "name": "Juan dela Cruz",
      "email": "customer1@marketplace.com"
    },
    "address": {
      "label": "Home",
      "phone": "09171234567",
      "address_line1": "123 Rizal Street",
      "city": "Makati",
      "province": "Metro Manila",
      "postal_code": "1200",
      "country": "Philippines"
    }
  }
}
```

**Error — Not Your Order `403`:**
```json
{
  "error_code": "FORBIDDEN",
  "message": "This action is unauthorized."
}
```

---

### 8. Update Order Status

Advances the order status **one step forward**, or allows **cancelling** the order if the current status is `pending`.

> **Controller:** `App\Http\Controllers\Api\Seller\OrderController` | **Method:** `updateStatus`

#### `PATCH /api/seller/orders/{id}/status`

**Example:** `PATCH /api/seller/orders/1/status`

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "status": "shipped"
}
```

**Valid Status Transitions (seller-controlled):**
```
pending → shipped → delivered
pending → cancelled
```

**Step-by-step demo flow:**

**Step 1 — Dispatch for delivery:**
```json
{ "status": "shipped" }
```

**Step 2 — Mark as delivered:**
```json
{ "status": "delivered" }
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

**Error — Invalid Transition `422`:**
```json
{
  "error_code": "INVALID_STATUS_TRANSITION",
  "message": "Invalid status transition from \"shipped\" to \"pending\"."
}
```

> You **cannot** move status backwards. `shipped → pending` is rejected. `delivered → shipped` is rejected.

**Error — Unauthorized Status `403`:**
```json
{
  "error_code": "FORBIDDEN",
  "message": "This action is unauthorized."
}
```

> **Sellers are strictly prohibited from changing an order status to `confirmed`.** Only the customer can confirm an order (which releases the held funds). Attempting to update the status to `confirmed` will result in a `403 Forbidden` error.

**Validation Rules:**
| Field | Rule |
|---|---|
| `status` | Required, must be one of: `pending`, `shipped`, `delivered`, `cancelled` |

---

### 9. Cancel Order

Allows a seller to cancel a `pending` order (e.g. due to stock/inventory issues). When cancelled, the product stock is restored, and the customer's wallet balance is refunded.


> **Controller:** `App\Http\Controllers\Api\Seller\OrderController` | **Method:** `cancel`

#### `PATCH /api/seller/orders/{id}/cancel`

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "cancel_reason": 5,
  "cancel_reason_notes": "Out of stock / mistake in inventory"
}
```

**Validation Rules:**
| Field | Required | Rule |
|---|---|---|
| `cancel_reason` | ✅ | Integer (`1` = change of mind, `2` = wrong address, `3` = duplication, `4` = delay, `5` = other) |
| `cancel_reason_notes` | Required if reason is `5` | String, max 255 characters |

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

**Error — Already Shipped `422`:**
```json
{
  "error_code": "ORDER_IN_TRANSIT",
  "message": "Order is already shipped and cannot be cancelled."
}
```

**Error — Invalid Status (Delivered/Confirmed) `422`:**
```json
{
  "error_code": "INVALID_STATUS_TRANSITION",
  "message": "Order cannot be cancelled in its current status: delivered"
}
```

---

## Order Status Flow (Seller Perspective)

```
[Customer places order]
        │
        ▼
   ┌─────────┐
   │ pending │  ◄── You receive the order here (Cancellation allowed here)
   └────┬────┘
        │  PATCH /status { "status": "shipped" }
        ▼
   ┌─────────┐
   │ shipped │  ◄── Item dispatched (Cannot cancel anymore)
   └────┬────┘
        │  PATCH /status { "status": "delivered" }
        ▼
   ┌───────────┐
   │ delivered │  ◄── Customer received item ✅
   └───────────┘
```

---

## SELLER PROFILE

### 12. Update Seller Profile

Updates the shop name and description of the authenticated seller.

> **Controller:** `App\Http\Controllers\Api\Seller\SellerProfileController` | **Method:** `update`

#### `PUT /api/seller/profile`

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
Accept: application/json
```

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
    "user_id": 2,
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
| `shop_name` | ✅ | String, max 255 chars, unique in `seller_profiles` table (ignoring current seller) |
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

| HTTP Code | Meaning | Exception Class | When it happens |
|---|---|---|---|
| `401` | Unauthenticated | `Illuminate\Auth\AuthenticationException` | No Bearer token, invalid or expired token |
| `403` | Forbidden | `Illuminate\Auth\Access\AuthorizationException` | Accessing another seller's resource or wrong role |
| `403` | Account Deactivated | `App\Exceptions\AccountDeactivatedException` | User's `is_active = false`; token immediately revoked |
| `404` | Not Found | `Symfony\Component\HttpKernel\Exception\NotFoundHttpException` | Product or order not found |
| `422` | Validation Error | `Illuminate\Validation\ValidationException` | Missing or invalid request fields |
| `422` | Invalid Status Transition | `App\Exceptions\InvalidStatusTransitionException` | Illegal status transition attempt (e.g. backward step or cancellation) |
| `429` | Too Many Requests | `Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException` | Exceeded 60 requests/minute |
| `500` | Server Error | `Throwable` (catch-all) | Unexpected server failure; error is logged, safe message returned |

**Example 500 response:**
```json
{
  "error_code": "SERVER_ERROR",
  "message": "Sorry, something went wrong on the server. Please try again later."
}
```
