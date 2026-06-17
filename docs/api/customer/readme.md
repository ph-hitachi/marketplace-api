# Customer API — Postman Reference

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

**Headers:**
```
Content-Type: application/json
```

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
| `address_line2` | ❌ | String, max 255 chars |
| `city` | ✅ | String, max 100 chars |
| `province` | ✅ | String, max 100 chars |
| `postal_code` | ✅ | String, max 20 chars |
| `country` | ❌ | Defaults to `Philippines` |

---

### 3. Get Single Address

> **Controller:** `App\Http\Controllers\Api\Customer\AddressController` | **Method:** `show`

#### `GET /api/customer/addresses/{id}`

**Example:** `GET /api/customer/addresses/1`

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

**Error — Accessing Another Customer's Address `403`:**
```json
{
  "error_code": "FORBIDDEN",
  "message": "You do not have permission to perform this action."
}
```

---

### 4. Update Address

Updates fields of an existing address. Only send the fields you want to change.

> **Controller:** `App\Http\Controllers\Api\Customer\AddressController` | **Method:** `update`

#### `PUT /api/customer/addresses/{id}`

**Request Body (full update example):**
```json
{
  "label": "Office (Updated)",
  "phone": "09281234567",
  "address_line1": "Unit 5B, BGC Corporate Tower",
  "address_line2": "32nd Street corner 5th Avenue",
  "city": "Taguig",
  "province": "Metro Manila",
  "postal_code": "1634",
  "country": "Philippines"
}
```

**Request Body (partial update example):**
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

**Validation Rules:**
| Field | Required | Rule | Description |
|---|---|---|---|
| `label` | ❌ | String, max 100 chars | Friendly name for address |
| `phone` | ❌ | String, max 30 chars, nullable | Contact number |
| `address_line1` | ❌ | String, max 255 chars | Street address |
| `address_line2` | ❌ | String, max 255 chars, nullable | Unit/Suite number etc. |
| `city` | ❌ | String, max 100 chars | City |
| `province` | ❌ | String, max 100 chars | Province/State |
| `postal_code` | ❌ | String, max 20 chars | Postal/ZIP code |
| `country` | ❌ | String, max 100 chars | Country |

---

### 5. Delete Address

Deletes an address. **Blocked** if the address is attached to any existing order.

> **Controller:** `App\Http\Controllers\Api\Customer\AddressController` | **Method:** `destroy`

#### `DELETE /api/customer/addresses/{id}`

**Success Response — `200 OK`:**
```json
{
  "message": "Address deleted."
}
```

**Error — Address Used in an Order `403`:**
```json
{
  "error_code": "FORBIDDEN",
  "message": "You do not have permission to perform this action."
}
```

---

### 6. Set Default Address

Sets the specified address as default. The previous default is automatically unset.

> **Controller:** `App\Http\Controllers\Api\Customer\AddressController` | **Method:** `setDefault`

#### `PATCH /api/customer/addresses/{id}/default`

No request body needed.

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

## CHECKOUTS

Carts act as a bookmark for products. You can add items to cart and view them later. Placing an order doesn't clear the cart items automatically.

### 7. List Carts

Returns all products bookmarked by the customer.

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

Bookmarks a product to cart.

> **Controller:** `App\Http\Controllers\Api\Customer\CartController` | **Method:** `store`

#### `POST /api/customer/carts`

**Headers:**
```
Content-Type: application/json
```

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

Removes a bookmarked product from cart.

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

Places an order for multiple products. The system automatically groups items by seller into separate orders under a shared batch reference. Wallet balance is deducted once for the grand total.

> **Controller:** `App\Http\Controllers\Api\Customer\OrderController` | **Method:** `store`

#### `POST /api/customer/orders`

**Headers:**
```
Content-Type: application/json
```

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
    },
    {
      "product_id": 2,
      "quantity": 1
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
      "seller_id": 2,
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

**Domain Error Responses:**

| Error | HTTP | `error_code` | Exception Class |
|---|---|---|---|
| Product inactive or deleted | `422` | `PRODUCT_UNAVAILABLE` | `App\Exceptions\ProductUnavailableException` |
| Requested qty > available stock | `422` | `INSUFFICIENT_STOCK` | `App\Exceptions\InsufficientStockException` |
| Balance < grand total | `422` | `INSUFFICIENT_BALANCE` | `App\Exceptions\InsufficientBalanceException` |

| Field | Required | Rule | Description |
|---|---|---|---|
| `address_id` | ✅ | Integer, must exist in your own addresses | Target delivery address ID |
| `payment_method` | ✅ | String, `in:wallet,cod` | Payment method to use |
| `wallet_id` | ❌ | Integer, required if `payment_method` is `wallet`, exists in your own wallets | Wallet ID to debit |
| `items` | ✅ | Array, min 1 | List of products to order |
| `items.*.product_id` | ✅ | Integer, exists in products | Product ID |
| `items.*.quantity` | ✅ | Integer, min 1 | Quantity to purchase |

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
      "seller_id": 3,
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
      "seller": { "seller_profile": { "shop_name": "Fashion Hub" } },
      "address": { "label": "Home", "city": "Makati" }
    }
  ],
  "per_page": 15,
  "total": 2
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
    "seller": { "seller_profile": { "shop_name": "Tech Haven" } },
    "address": { "label": "Home", "address_line1": "123 Rizal Street", "city": "Makati" }
  }
}
```

**Error — Viewing Another Customer's Order `403`:**
```json
{
  "error_code": "FORBIDDEN",
  "message": "You do not have permission to perform this action."
}
```

---

### 13. Cancel Order

Cancels a **pending** or **delivered** order. Restores the product stock and refunds the full transaction amount back to the customer's wallet if paid via wallet payment. Requires an integer `cancel_reason` code, and optional `cancel_reason_notes` if reason is `5` ("Other").

> **Controller:** `App\Http\Controllers\Api\Customer\OrderController` | **Method:** `cancel`

#### `PATCH /api/customer/orders/{id}/cancel`

**Headers:**
```
Content-Type: application/json
```

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

**Validation Rules:**
| Field | Required | Rule | Description |
|---|---|---|---|
| `cancel_reason` | ✅ | Integer, `in:1,2,3,4,5` | Cancellation reason code |
| `cancel_reason_notes` | ❌ | String, required if `cancel_reason` is `5`, `max:255` | Notes details |

**Cancel Reason Mapping:**
* `1` => Customer change of mind
* `2` => Wrong shipping address
* `3` => Order duplication
* `4` => Seller delay
* `5` => Other (requires notes)

**Domain Error Responses:**
* **`422 ORDER_IN_TRANSIT`**: Returned if the order is already in `'shipped'` status and cannot be cancelled.
  ```json
  {
    "error_code": "ORDER_IN_TRANSIT",
    "message": "Cannot cancel the order while it is in shipped status."
  }
  ```
* **`422 INVALID_STATUS_TRANSITION`**: Returned if the order is already `'confirmed'` or `'cancelled'`.

---

### 14. Confirm Order

Confirms delivery/acceptance of an order. This transitions the order's status to `'confirmed'`, sets the `completed_at` timestamp, and releases the held payment from `'on_hold'` status to credit the seller's wallet with type `'sales'`.

> **Controller:** `App\Http\Controllers\Api\Customer\OrderController` | **Method:** `confirm`

#### `POST /api/customer/orders/{id}/confirm`

**Success Response — `200 OK`:**
```json
{
  "message": "Order confirmed and payment released."
}
```

**Domain Error Responses:**
* **`422 INVALID_STATUS_TRANSITION`**: Returned if the order is already `'confirmed'` or `'cancelled'`.

---

## Order Status Flow

```
pending ──► shipped ──► delivered ──► confirmed (funds released)
   │                                      │
   └──────────────────────────────────────┴──► cancelled (restores stock / refunds)
```

| Status | Who sets it | Meaning |
|---|---|---|
| `pending` | System on order creation | Awaiting seller acknowledgment |
| `shipped` | Seller | Item dispatched, transit in progress |
| `delivered` | Seller | Customer received item |
| `confirmed` | Customer | Order accepted, terminal state, releases held wallet funds to seller |
| `cancelled` | Customer (pending/delivered) / Admin | Order cancelled (restores stock & refunds customer) |

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
| `403` | Forbidden | `Illuminate\Auth\Access\AuthorizationException` | Wrong role, or policy rejected the action |
| `403` | Account Deactivated | `App\Exceptions\AccountDeactivatedException` | User's `is_active = false`; token immediately revoked |
| `404` | Not Found | `Symfony\...\NotFoundHttpException` | Route or model does not exist |
| `422` | Validation Error | `Illuminate\Validation\ValidationException` | Missing or invalid request fields |
| `422` | Insufficient Balance | `App\Exceptions\InsufficientBalanceException` | Wallet balance < order total |
| `422` | Insufficient Stock | `App\Exceptions\InsufficientStockException` | Requested qty > available stock |
| `422` | Product Unavailable | `App\Exceptions\ProductUnavailableException` | Product is inactive, deleted, or not found |
| `422` | Order In Transit | `App\Exceptions\OrderInTransitException` | Trying to cancel order in processing/shipped status |
| `422` | Invalid Status Transition | `App\Exceptions\InvalidStatusTransitionException` | Prohibited order status transitions |
| `429` | Too Many Requests | `Symfony\...\TooManyRequestsHttpException` | Exceeded 60 requests/minute |
| `500` | Server Error | `Throwable` (catch-all) | Unexpected server failure; error is logged, safe message returned |

**Example 500 response:**
```json
{
  "error_code": "SERVER_ERROR",
  "message": "Sorry, something went wrong on the server. Please try again later."
}
```
