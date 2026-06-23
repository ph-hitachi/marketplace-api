# Customer API — Reference

> **Base URL:** `http://localhost/api/customer`
> **Auth Required:** ✅ Bearer Token (`role = customer`)
> **Rate Limit:** 60 requests/minute per token

All requests must include:
```
Authorization: Bearer <your_token>
Accept: application/json
```

> Get your token: `POST /api/auth/login` with `customer1@marketplace.com` / `Customer1234!`

---

## Table of Contents

### Addresses
1. [List Addresses](#1-list-addresses)
2. [Create Address](#2-create-address)
3. [Get Single Address](#3-get-single-address)
4. [Update Address](#4-update-address)
5. [Delete Address](#5-delete-address)
6. [Set Default Address](#6-set-default-address)

### Carts
7. [List Carts](#7-list-carts)
8. [Bookmark to Cart](#8-bookmark-to-cart)
9. [Remove from Cart](#9-remove-from-cart)

### Orders
10. [Place Order](#10-place-order)
11. [List My Orders](#11-list-my-orders)
12. [Get Single Order](#12-get-single-order)
13. [Cancel Order](#13-cancel-order)
14. [Confirm Order](#14-confirm-order)

---

## WALLET

Wallet endpoints have been migrated to a shared user namespace to support both customers and sellers.
Please refer to the [User Wallet Management Reference](../user/readme.md) for full details on listing, creating, setting defaults, and topping up wallets.

---

## ADDRESSES

### 1. List Addresses

Returns all saved delivery addresses for the authenticated customer.

> **Controller:** `App\Http\Controllers\Api\Customer\AddressController` | **Method:** `index`

#### `GET /api/customer/addresses`

**Success Response — `200 OK`:**
```json
{
  "addresses": [
    {
      "id": 1,
      "user_id": 4,
      "label": "Home",
      "phone": "09171234567",
      "address_line1": "123 Rizal Street",
      "address_line2": null,
      "city": "Makati",
      "province": "Metro Manila",
      "postal_code": "1200",
      "country": "Philippines",
      "is_default": true
    }
  ]
}
```

---

### 2. Create Address

Creates a new delivery address. The **first address** created is automatically set as default.

> **Controller:** `App\Http\Controllers\Api\Customer\AddressController` | **Method:** `store`

#### `POST /api/customer/addresses`

**Request Body:**
```json
{
  "label": "Office",
  "phone": "09281234567",
  "address_line1": "Unit 5B, BGC Corporate Tower",
  "address_line2": "32nd Street corner 5th Avenue",
  "city": "Taguig",
  "province": "Metro Manila",
  "postal_code": "1634",
  "country": "Philippines"
}
```

**Success Response — `201 Created`:**
```json
{
  "address": {
    "id": 3,
    "user_id": 4,
    "label": "Office",
    "phone": "09281234567",
    "address_line1": "Unit 5B, BGC Corporate Tower",
    "address_line2": "32nd Street corner 5th Avenue",
    "city": "Taguig",
    "province": "Metro Manila",
    "postal_code": "1634",
    "country": "Philippines",
    "is_default": false
  }
}
```

**Validation Rules:**
| Field | Required | Rule |
|---|---|---|
| `label` | ✅ | String, max 100 chars |
| `phone` | ❌ | String, max 30 chars |
| `address_line1` | ✅ | String, max 255 chars |
| `city` | ✅ | String, max 100 chars |
| `province` | ✅ | String, max 100 chars |
| `postal_code` | ✅ | String, max 20 chars |

---

### 3. Get Single Address

> **Controller:** `App\Http\Controllers\Api\Customer\AddressController` | **Method:** `show`

#### `GET /api/customer/addresses/{id}`

**Success Response — `200 OK`:**
```json
{
  "address": {
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
}
```

---

### 4. Update Address

> **Controller:** `App\Http\Controllers\Api\Customer\AddressController` | **Method:** `update`

#### `PUT /api/customer/addresses/{id}`

**Request Body:**
```json
{
  "label": "Home (Updated)",
  "phone": "09991234567",
  "city": "Pasig"
}
```

**Success Response — `200 OK`:**
```json
{
  "address": {
    "id": 1,
    "label": "Home (Updated)",
    "phone": "09991234567",
    "address_line1": "123 Rizal Street",
    "city": "Pasig",
    "province": "Metro Manila",
    "postal_code": "1200",
    "country": "Philippines",
    "is_default": true
  }
}
```

---

### 5. Delete Address

> **Controller:** `App\Http\Controllers\Api\Customer\AddressController` | **Method:** `destroy`

#### `DELETE /api/customer/addresses/{id}`

**Success Response — `200 OK`:**
```json
{
  "message": "Address deleted."
}
```

---

### 6. Set Default Address

> **Controller:** `App\Http\Controllers\Api\Customer\AddressController` | **Method:** `setDefault`

#### `PATCH /api/customer/addresses/{id}/default`

**Success Response — `200 OK`:**
```json
{
  "message": "Default address updated.",
  "address": {
    "id": 3,
    "label": "Office",
    "is_default": true
  }
}
```

---

## CARTS

### 7. List Carts

> **Controller:** `App\Http\Controllers\Api\Customer\CartController` | **Method:** `index`

#### `GET /api/customer/carts`

**Success Response — `200 OK`:**
```json
[
  {
    "id": 1,
    "customer_id": 4,
    "product_id": 1,
    "created_at": "2024-05-12T10:00:00.000000Z",
    "product": {
      "id": 1,
      "name": "Wireless Earbuds Pro",
      "price": "1299.00"
    }
  }
]
```

---

### 8. Bookmark to Cart

> **Controller:** `App\Http\Controllers\Api\Customer\CartController` | **Method:** `store`

#### `POST /api/customer/carts`

**Request Body:**
```json
{
  "product_id": 1
}
```

**Success Response — `201 Created`:**
```json
{
  "message": "Product bookmarked to cart.",
  "cart": {
    "id": 1,
    "customer_id": 4,
    "product_id": 1,
    "product": {
      "id": 1,
      "name": "Wireless Earbuds Pro",
      "price": "1299.00"
    }
  }
}
```

---

### 9. Remove from Cart

> **Controller:** `App\Http\Controllers\Api\Customer\CartController` | **Method:** `destroy`

#### `DELETE /api/customer/carts/{id}`

**Success Response — `200 OK`:**
```json
{
  "message": "Product removed from cart."
}
```

---

## ORDERS

### 10. Place Order

Places an order for multiple products. The system automatically groups items by seller shop into separate orders under a shared batch reference.

> **Controller:** `App\Http\Controllers\Api\Customer\OrderController` | **Method:** `store`

#### `POST /api/customer/orders`

**Request Body:**
```json
{
  "address_id": 1,
  "payment_method": "wallet",
  "wallet_id": 1,
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    }
  ]
}
```

**Success Response — `201 Created`:**
```json
{
  "message": "Orders placed successfully.",
  "batch_ref": "123e4567-e89b-12d3-a456-426614174000",
  "orders": [
    {
      "id": 1,
      "customer_id": 4,
      "shop_id": 2,
      "address_id": 1,
      "status": "pending",
      "total_amount": "2598.00",
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
  ],
  "total_deducted": 2598.00,
  "balance_remaining": 500.00
}
```

---

### 11. List My Orders

Returns a paginated list of all orders placed by the authenticated customer.

> **Controller:** `App\Http\Controllers\Api\Customer\OrderController` | **Method:** `index`

#### `GET /api/customer/orders`

**Success Response — `200 OK`:**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 2,
      "shop_id": 3,
      "status": "pending",
      "total_amount": "1850.00",
      "items": [
        {
          "id": 2,
          "product_name": "Classic White Sneakers",
          "unit_price": "1850.00",
          "quantity": 1,
          "subtotal": "1850.00"
        }
      ],
      "shop": {
        "shop_name": "Fashion Hub",
        "shop_description": "Latest clothing and trends."
      },
      "address": { "label": "Home", "city": "Makati" }
    }
  ],
  "per_page": 15,
  "total": 1
}
```

---

### 12. Get Single Order

> **Controller:** `App\Http\Controllers\Api\Customer\OrderController` | **Method:** `show`

#### `GET /api/customer/orders/{id}`

**Success Response — `200 OK`:**
```json
{
  "order": {
    "id": 1,
    "status": "pending",
    "total_amount": "2598.00",
    "items": [
      {
        "id": 1,
        "product_name": "Wireless Earbuds Pro",
        "unit_price": "1299.00",
        "quantity": 2,
        "subtotal": "2598.00"
      }
    ],
    "shop": {
      "shop_name": "Tech Haven",
      "shop_description": "Gadgets and electronics."
    },
    "address": { "label": "Home", "address_line1": "123 Rizal Street", "city": "Makati" }
  }
}
```

---

### 13. Cancel Order

> **Controller:** `App\Http\Controllers\Api\Customer\OrderController` | **Method:** `cancel`

#### `PATCH /api/customer/orders/{id}/cancel`

**Request Body:**
```json
{
  "cancel_reason": 5,
  "cancel_reason_notes": "The product had a major technical defect on arrival."
}
```

**Success Response — `200 OK`:**
```json
{
  "message": "Order cancelled and refund issued."
}
```

---

### 14. Confirm Order

> **Controller:** `App\Http\Controllers\Api\Customer\OrderController` | **Method:** `confirm`

#### `POST /api/customer/orders/{id}/confirm`

**Success Response — `200 OK`:**
```json
{
  "message": "Order confirmed and payment released."
}
```

---

## Order Status Flow

```
pending ──► shipped ──► delivered ──► confirmed (funds released)
   │                                      │
   └──────────────────────────────────────┴──► cancelled (restores stock / refunds)
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
