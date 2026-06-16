# User API — Wallet Management Reference

All routes below require Bearer Token Authentication via `auth:sanctum` and are prefixed with `/api/user`.

---

## Shared Wallet Endpoints

These endpoints are shared by both `customer` and `seller` accounts.

### 1. List Wallets
* **Method**: `GET`
* **Route**: `/api/user/wallets`
* **Description**: Retrieve a list of all wallets owned by the authenticated user.
* **Headers**:
  ```http
  Authorization: Bearer <token>
  Accept: application/json
  ```
* **Success Response (200 OK)**:
  ```json
  [
    {
      "id": 1,
      "user_id": 4,
      "label": "Default",
      "balance": "1500.00",
      "is_default": true,
      "created_at": "2026-06-14T15:00:00.000000Z",
      "updated_at": "2026-06-14T15:30:00.000000Z"
    },
    {
      "id": 2,
      "user_id": 4,
      "label": "Savings",
      "balance": "0.00",
      "is_default": false,
      "created_at": "2026-06-14T15:10:00.000000Z",
      "updated_at": "2026-06-14T15:10:00.000000Z"
    }
  ]
  ```

---

### 2. Create Wallet
* **Method**: `POST`
* **Route**: `/api/user/wallets`
* **Description**: Create a new custom wallet. The first wallet created is automatically marked as default if none exists. Balance starts at `0.00` (mass assignment protected).
* **Headers**:
  ```http
  Authorization: Bearer <token>
  Content-Type: application/json
  Accept: application/json
  ```
* **Request Body**:
  ```json
  {
    "label": "Business Wallet"
  }
  ```
* **Success Response (201 Created)**:
  ```json
  {
    "message": "Wallet created successfully.",
    "wallet": {
      "id": 3,
      "user_id": 4,
      "label": "Business Wallet",
      "balance": "0.00",
      "is_default": false,
      "created_at": "2026-06-14T15:32:00.000000Z",
      "updated_at": "2026-06-14T15:32:00.000000Z"
    }
  }
  ```
* **Validation Rules**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `label` | string | `required`, `max:100` | The name or tag for the wallet |

---

### 3. View Wallet Details & Transactions
* **Method**: `GET`
* **Route**: `/api/user/wallets/{id}`
* **Description**: View specific wallet balance and the latest 10 transactions.
* **Headers**:
  ```http
  Authorization: Bearer <token>
  Accept: application/json
  ```
* **Success Response (200 OK)**:
  ```json
  {
    "wallet": {
      "id": 1,
      "user_id": 4,
      "label": "Default",
      "balance": "1500.00",
      "is_default": true,
      "created_at": "2026-06-14T15:00:00.000000Z",
      "updated_at": "2026-06-14T15:30:00.000000Z"
    },
    "transactions": [
      {
        "id": 4,
        "wallet_id": 1,
        "type": "purchase",
        "amount": "250.00",
        "balance_before": "1750.00",
        "balance_after": "1500.00",
        "reference_id": 12,
        "status": "on_hold",
        "description": "Payment for order #12",
        "created_at": "2026-06-14T15:30:00.000000Z"
      },
      {
        "id": 3,
        "wallet_id": 1,
        "type": "topup",
        "amount": "1000.00",
        "balance_before": "750.00",
        "balance_after": "1750.00",
        "reference_id": null,
        "status": "completed",
        "description": "Wallet top-up",
        "created_at": "2026-06-14T15:05:00.000000Z"
      }
    ]
  }
  ```
* **Common Errors**:
  - `403 Forbidden` (IDOR check): Returns if the wallet belongs to another user.
  - `404 Not Found`: Returns if the wallet ID does not exist.

---

### 4. Top-up Wallet
* **Method**: `POST`
* **Route**: `/api/user/wallets/{id}/topup`
* **Description**: Deposit money into a specific wallet. Logs a transaction of type `'topup'`, status `'completed'`.
* **Headers**:
  ```http
  Authorization: Bearer <token>
  Content-Type: application/json
  Accept: application/json
  ```
* **Request Body**:
  ```json
  {
    "amount": 500.00
  }
  ```
* **Success Response (201 Created)**:
  ```json
  {
    "message": "Top-up successful.",
    "transaction": {
      "id": 5,
      "wallet_id": 1,
      "type": "topup",
      "amount": "500.00",
      "balance_before": "1500.00",
      "balance_after": "2000.00",
      "reference_id": null,
      "status": "completed",
      "description": "Wallet top-up",
      "created_at": "2026-06-14T15:35:00.000000Z"
    },
    "balance": "2000.00"
  }
  ```
* **Validation Rules**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `amount` | numeric | `required`, `min:1.00`, `max:50000.00` | The money amount to deposit |

---

### 5. Set Default Wallet
* **Method**: `POST`
* **Route**: `/api/user/wallets/{id}/default`
* **Description**: Sets the specified wallet as default for the user, resetting default status on all other wallets owned by the user.
* **Headers**:
  ```http
  Authorization: Bearer <token>
  Accept: application/json
  ```
* **Success Response (200 OK)**:
  ```json
  {
    "message": "Default wallet updated.",
    "wallet": {
      "id": 2,
      "user_id": 4,
      "label": "Savings",
      "balance": "0.00",
      "is_default": true,
      "created_at": "2026-06-14T15:10:00.000000Z",
      "updated_at": "2026-06-14T15:40:00.000000Z"
    }
  }
}
```

---


### 6. Update User Profile
* **Method**: `PUT`
* **Route**: `/api/user/profile`
* **Description**: Update the authenticated user's name, email, or password. Changing `role` is strictly ignored/prevented.
* **Headers**:
  ```http
  Authorization: Bearer <token>
  Content-Type: application/json
  Accept: application/json
  ```
* **Request Body**:
  ```json
  {
    "name": "New Name",
    "email": "newemail@marketplace.com",
    "password": "NewPassword123!",
    "password_confirmation": "NewPassword123!"
  }
  ```
* **Success Response (200 OK)**:
  ```json
  {
    "message": "Profile updated successfully.",
    "user": {
      "id": 4,
      "name": "New Name",
      "email": "newemail@marketplace.com",
      "role": "customer",
      "is_active": true,
      "created_at": "2026-06-14T15:00:00.000000Z",
      "updated_at": "2026-06-14T15:45:00.000000Z"
    }
  }
  ```
* **Validation Rules**:
  | Field | Type | Rules | Description |
  | :--- | :--- | :--- | :--- |
  | `name` | string | `required`, `max:255` | The user's full name |
  | `email` | string | `required`, `email`, `max:255`, `unique:users` | Must be a unique email (except current user) |
  | `password` | string | `nullable`, `confirmed`, `min:8` (letters + numbers) | Optional new password |
