# API Overview

Welcome to the Marketplace API documentation.

## 1. Endpoints Summary

### Admin/Orders

#### Order

| Method | Endpoint | Success Status | Description |
|---|---|---|---|
| GET | `/api/admin/orders` | `200 OK` | List all orders |
| GET | `/api/admin/orders/{order}` | `200 OK` | View any order details |

### Admin/Users

#### User

| Method | Endpoint | Success Status | Description |
|---|---|---|---|
| GET | `/api/admin/users` | `200 OK` | List registered users |
| GET | `/api/admin/users/{user}` | `200 OK` | View specific user |
| DELETE | `/api/admin/users/{user}` | `204 No Content` | Delete user account |
| PATCH | `/api/admin/users/{user}/activate` | `204 No Content` | Reactivate user |
| PATCH | `/api/admin/users/{user}/deactivate` | `204 No Content` | Deactivate user |

### Auth

| Method | Endpoint | Success Status | Description |
|---|---|---|---|
| POST | `/api/auth/register` | `201 Created` | Register a new customer or seller account |
| POST | `/api/auth/login` | `200 OK` | Authenticate a user and receive a token |
| POST | `/api/auth/logout` | `200 OK` | Log out and revoke token |
| POST | `/api/auth/refresh` | `200 OK` | Exchange token |

### Customer/Addresses

#### Address

| Method | Endpoint | Success Status | Description |
|---|---|---|---|
| GET | `/api/customer/addresses` | `200 OK` | List saved addresses |
| POST | `/api/customer/addresses` | `201 Created` | Add a new address |
| GET | `/api/customer/addresses/{address}` | `200 OK` | View a delivery address |
| PUT | `/api/customer/addresses/{address}` | `200 OK` | Update address |
| DELETE | `/api/customer/addresses/{address}` | `204 No Content` | Delete address |
| PATCH | `/api/customer/addresses/{address}/default` | `200 OK` | Set default address |

### Customer/Cart

#### Cart

| Method | Endpoint | Success Status | Description |
|---|---|---|---|
| GET | `/api/customer/carts` | `200 OK` | List saved items in the cart |
| POST | `/api/customer/carts` | `201 Created` | Add an item to the cart |
| DELETE | `/api/customer/carts/{cart}` | `200 OK` | Remove an item from the cart |

### Customer/Orders

#### Order

| Method | Endpoint | Success Status | Description |
|---|---|---|---|
| POST | `/api/customer/orders` | `200 OK` | Place order |
| GET | `/api/customer/orders` | `200 OK` | List orders |
| GET | `/api/customer/orders/{order}` | `200 OK` | View order details |
| PATCH | `/api/customer/orders/{order}/cancel` | `200 OK` | Cancel order |
| POST | `/api/customer/orders/{order}/confirm` | `200 OK` | Confirm receipt to release funds |

### Public/Products

#### Product

| Method | Endpoint | Success Status | Description |
|---|---|---|---|
| GET | `/api/products` | `200 OK` | List all active products |
| GET | `/api/products/{id}` | `200 OK` | Retrieve details for an active product |

### Seller/Orders

#### Order

| Method | Endpoint | Success Status | Description |
|---|---|---|---|
| GET | `/api/seller/orders` | `200 OK` | No description provided. |
| GET | `/api/seller/orders/{order}` | `200 OK` | View specific seller order |
| PATCH | `/api/seller/orders/{order}/status` | `200 OK` | Advance status |
| PATCH | `/api/seller/orders/{order}/cancel` | `200 OK` | Cancel order |

### Seller/Products

#### Product

| Method | Endpoint | Success Status | Description |
|---|---|---|---|
| GET | `/api/seller/products` | `200 OK` | List seller shop products |
| POST | `/api/seller/products` | `201 Created` | Add new product |
| GET | `/api/seller/products/{product}` | `200 OK` | View specific product |
| PUT | `/api/seller/products/{product}` | `200 OK` | Update product details |
| DELETE | `/api/seller/products/{product}` | `204 No Content` | Soft-delete product |
| PATCH | `/api/seller/products/{product}/activate` | `204 No Content` | Mark active |
| PATCH | `/api/seller/products/{product}/deactivate` | `204 No Content` | Mark inactive |

### Seller/Profile

#### ShopProfile

| Method | Endpoint | Success Status | Description |
|---|---|---|---|
| PUT | `/api/seller/profile` | `200 OK` | Update shop profile |

### Shops

#### Shop

| Method | Endpoint | Success Status | Description |
|---|---|---|---|
| GET | `/api/shops` | `200 OK` | List all available shops |
| GET | `/api/shops/{shop}` | `200 OK` | Retrieve a specific shop profile and active products |

### User/Profile

#### Profile

| Method | Endpoint | Success Status | Description |
|---|---|---|---|
| GET | `/api/user/me` | `200 OK` | Retrieve authenticated user profile |
| PUT | `/api/user/profile` | `200 OK` | Update profile details |

### User/Wallet

#### Wallet

| Method | Endpoint | Success Status | Description |
|---|---|---|---|
| GET | `/api/user/wallets` | `200 OK` | List user wallets |
| POST | `/api/user/wallets` | `201 Created` | Create new wallet |
| GET | `/api/user/wallets/{wallet}` | `200 OK` | View specific wallet details |
| POST | `/api/user/wallets/{wallet}/topup` | `201 Created` | Add funds |
| POST | `/api/user/wallets/{wallet}/default` | `200 OK` | Set default payment wallet |

---

## 2. Standard HTTP Response Codes

While domain-specific errors (`ServerErrorException`) have their own dedicated error codes, the API also relies heavily on standard HTTP status codes.

### Success Responses

| HTTP Status | Description |
|---|---|
| `200 OK` | The request succeeded. Used for `GET` (fetching records), `PUT`/`PATCH` (updating records), and standard actions. |
| `201 Created` | The request succeeded and a new resource was created. Used exclusively for `POST` requests that result in database creation. |
| `204 No Content` | The request succeeded, but there is no body to return. Used primarily for `DELETE` requests where the resource is successfully removed. |

---

## 3. Global Error Responses

The API uses a standardized error format for all exceptions. The `app.php` bootstrap configuration enforces the following global error codes:

| Error Code | HTTP Status | Exception Type | Description |
|---|---|---|---|
| `TOKEN_COULD_NOT_PARSE` | `400 Bad Request` | `JWTException` | The token could not be parsed. |
| `BAD_REQUEST` | `400 Bad Request` | `BadRequestHttpException` | The request could not be understood or was malformed. |
| `TOKEN_BLACKLISTED` | `401 Unauthorized` | `TokenBlacklistedException` | The token has been blacklisted. |
| `TOKEN_EXPIRED` | `401 Unauthorized` | `TokenExpiredException` | The token has expired. |
| `TOKEN_INVALID` | `401 Unauthorized` | `TokenInvalidException` | The token is invalid. |
| `UNAUTHENTICATED` | `401 Unauthorized` | `AuthenticationException` | You are not authenticated. Please provide a valid token. |
| `FORBIDDEN` | `403 Forbidden` | `AuthorizationException` | You do not have permission to perform this action. |
| `FORBIDDEN` | `403 Forbidden` | `AccessDeniedHttpException` | Attempting to perform an action without required permissions. |
| `NOT_FOUND` | `404 Not Found` | `NotFoundHttpException` | Requesting an endpoint or database record that does not exist. |
| `NOT_FOUND` | `404 Not Found` | `ModelNotFoundException` | The database record does not exist. |
| `METHOD_NOT_ALLOWED` | `405 Method Not Allowed` | `MethodNotAllowedHttpException` | Invalid HTTP method. |
| `VALIDATION_ERROR` | `422 Unprocessable Entity` | `ValidationException` | The given data was invalid. |
| `TOO_MANY_REQUESTS` | `429 Too Many Requests` | `TooManyRequestsHttpException` | Too many requests. Please slow down and try again in a moment. |
| `INTERNAL_ERROR` | `500 Internal Server Error` | `\Throwable` | Sorry, something went wrong on the server. Please try again later. |


### Global Error JSON Format

When a global error occurs, the API returns a structured JSON response matching the domain exception format:

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
| `InvalidStatusTransitionException` | `409` | `INVALID_STATUS_TRANSITION` | Attempting to move an order to an illogical state (e.g., pending to delivered). |
| `OrderInTransitException` | `409` | `ORDER_IN_TRANSIT` | Attempting to cancel an order that has already been shipped. |
| `ProductUnavailableException` | `422` | `PRODUCT_UNAVAILABLE` | Attempting to purchase a product that is inactive or deleted. |
| `UserDeleteBlockedException` | `409` | `DELETE_BLOCKED` | Attempting to delete a user that has active orders tied to it. |

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

### API Rate Limiting & Authentication

The API applies a strict rate limit of **60 requests per minute** per IP Address or Authenticated User ID.
When the limit is reached, the API returns a `429 Too Many Requests` status code (`TOO_MANY_REQUESTS` global error code).

Response headers included on every request to track your limit:
- `X-RateLimit-Limit`: Maximum requests allowed per minute (60)
- `X-RateLimit-Remaining`: Number of requests remaining in the current period
- `Retry-After`: (On a 429 response) Seconds to wait before making another request

#### Security & Authentication Schemes

- **http** (Type: `http`): Protected endpoints require this authentication scheme.

### HTTP Security Headers

| Header | Value | Purpose |
|---|---|---|
| `X-Content-Type-Options` | `nosniff` | Prevent browsers from misinterpreting the content type (MIME-sniffing) |
| `X-Frame-Options` | `DENY` | Prevent clickjacking by blocking the API from being embedded in an iframe |
| `X-XSS-Protection` | `1; mode=block` | Enable strict cross-site scripting (XSS) filtering |
| `Referrer-Policy` | `no-referrer` | Ensure no referrer information is leaked to third parties |
| `Content-Security-Policy` | `default-src 'none'; script-src 'none'; object-src 'none'; base-uri 'none'; frame-ancestors 'none';` | Advanced Content Security Policy for API (block all active content, framing, and explicitly prevent SVG/XSS script execution) |
| `Cache-Control` | `no-store, max-age=0, must-revalidate` | Dynamic Cache-Control to prevent caching of sensitive data (Web Deception Protection) |

### CORS Configuration

The Marketplace API implements Cross-Origin Resource Sharing (CORS) policies to control which external domains are allowed to access resources.

The current CORS configuration is dynamically parsed below:

| CORS Directive | Configured Value | Description |
|---|---|---|
| **Allowed Paths** | `api/*, sanctum/csrf-cookie` | Paths for which cross-origin requests are enabled. |
| **Allowed Origins** | `*` | Allowed origins (domains) that can access the API. |
| **Allowed Methods** | `*` | HTTP methods permitted when accessing the resource. |
| **Allowed Headers** | `*` | HTTP headers that can be used during the actual request. |
| **Supports Credentials** | `false` | Indicates whether the request can be made using credentials (cookies, HTTP auth). |
| **Max Age** | `0` | Seconds the results of a preflight request can be cached. |

### JWT Security Mechanisms

JSON Web Tokens (JWTs) are a secure way to authenticate users in stateless environments. Here's how the security is maintained:

- **Token Expiration**: JWT tokens have an expiration time (expiry). After a token expires, it's no longer valid for authentication. This ensures that if a token is intercepted, it can only be used for a limited time.

- **Token Refresh**: When an access token expires, the user can use the refresh token to obtain a new access token without having to re-enter their credentials. The refresh token is typically long-lived and is used to generate new access tokens.

- **Token Blacklisting**: While expired tokens can't be used for authentication, they can be blacklisted to ensure that even if an attacker gets hold of a valid token, it won't work after being blacklisted. Laravel has a built-in blacklist mechanism for this purpose.

- **Statelessness**: JWTs are self-contained and do not require server-side storage. This makes JWT-based authentication suitable for scalable and distributed systems.

### Handling Expired Tokens and Refreshing

When an access token expires, the user can use the refresh token to request a new access token. This is done by making a request to the `/api/auth/refresh` endpoint, providing the expired token in the authorization header. The API then responds with a new access token, extending the user's session.

### Blacklisting Tokens

If a token is compromised or a user logs out, their tokens can be blacklisted. Blacklisting means that even if an expired token is used for refresh, the new access token won't be generated. Laravel's built-in mechanism takes care of blacklisting tokens to enhance security.

