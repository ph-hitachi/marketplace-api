# Laravel JWT API Starter (Dockerized)

A boilerplate Laravel JWT-based API starter kit, pre-configured with a modern Docker environment (PHP 8.1 FPM, Nginx, MySQL, Redis) and robust API documentation and database tools.

---

## 🚀 Quick Start

1. **Build and start the stack (from project root):**
   ```bash
   docker compose up -d --build
   ```
   *The PHP container installs Composer and will auto-install Laravel into `src` on the first container start if it is empty. Wait for logs to finish.*

2. **Copy `.env.docker` to `src/.env` (or edit `src/.env`) and run migrations:**
   ```bash
   docker compose exec app php artisan migrate
   ```

3. **Access the application:**
   Point your browser to `http://localhost`.

---

## 🛠️ Running Artisan Commands

Run standard Artisan commands directly in the running `app` container:
- **Run migrations:**
  ```bash
  docker compose exec app php artisan migrate
  ```
- **Run a one-off artisan command if the container is not running:**
  ```bash
  docker compose run --rm app php artisan migrate
  ```
- **Open a shell inside the `app` container:**
  ```bash
  docker compose exec app sh
  ```
- **Follow application logs:**
  ```bash
  docker compose logs -f app
  ```

---

## 📄 API & Database Documentation Tools

This starter includes dynamic generators for API documentation, Postman collections, and database schema layouts.

### 1. Interactive API Docs (OpenAPI / Stoplight Elements)
The API documentation is built using OpenAPI and rendered beautifully via **Stoplight Elements**.
- **Interactive UI**: Open [docs/api/index.html](file:///c:/Users/JessD/Desktop/Gcash/laravel-jwt-api-starter/docs/api/index.html) in your local browser to view the interactive API sandbox.
- **Specification File**: The raw OpenAPI schema is located at [docs/api/openapi.json](file:///c:/Users/JessD/Desktop/Gcash/laravel-jwt-api-starter/docs/api/openapi.json).
- **Markdown Overview**: A detailed text list of all endpoints, parameters, responses, and errors is available at [docs/api/overview.md](file:///c:/Users/JessD/Desktop/Gcash/laravel-jwt-api-starter/docs/api/overview.md).

#### 🔄 How to Regenerate API Docs:
Run the dynamic overview and OpenAPI regeneration command:
```bash
docker compose exec app php artisan overview:generate
```
*This command executes Scramble to update `openapi.json`, regenerates the Postman collection, sorts tags/paths by display weights defined in controllers, and rebuilds `overview.md`.*

---

### 2. Postman Collection
An automated Postman collection is exported to facilitate immediate API testing and import.
- **Collection File**: Import the pre-configured [docs/api/postman_collection.json](file:///c:/Users/JessD/Desktop/Gcash/laravel-jwt-api-starter/docs/api/postman_collection.json) directly into Postman.
- **Environment Support**: Pre-populated with default variables `{{base_url}}` (defaults to `http://localhost/api`) and `{{token}}` for seamless authentication routing.
- **Dynamic Post Bodies**: Auto-fills post body parameters with simulated mock data based on input schemas.

#### 🔄 How to Regenerate Postman Collection:
You can regenerate the collection manually from the OpenAPI schema:
```bash
docker compose exec app php artisan postman:generate
```

---

### 3. Database Schema Diagram (DBML)
DBML (Database Markup Language) is a simple syntax used to define and visualize database schemas.
- **DBML Schema File**: Located at [docs/database.dbml](file:///c:/Users/JessD/Desktop/Gcash/laravel-jwt-api-starter/docs/database.dbml).
- **Visualization**: Copy and paste the contents of `database.dbml` into [dbdiagram.io](https://dbdiagram.io) to view an interactive Entity Relationship (ER) Diagram.

#### 🔄 How to Regenerate DBML:
Whenever you add or modify migrations, run this command to parse the tables and foreign keys into DBML format:
```bash
docker compose exec app php artisan dbml:generate
```


