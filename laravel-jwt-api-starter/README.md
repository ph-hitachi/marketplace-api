# Docker Compose for Laravel (PHP 8, Nginx, MySQL)

Files added:

- `docker-compose.yml` — defines `app` (php-fpm), `web` (nginx) and `db` (mysql)
- `docker/php-fpm/Dockerfile` — PHP 8.1 FPM image with common extensions and Composer
- `docker/php-fpm/php.ini` — minimal PHP settings
- `docker/nginx/conf.d/app.conf` — Nginx vhost for Laravel `public` folder
- `.env.docker` — example DB env for Laravel

Quick start (from project root):

```bash
docker compose up -d --build
```

The PHP image now installs Composer and will auto-install Laravel into `src` on first container start if `src` is empty.

Workflow:

1. Build and start the stack:

```bash
docker compose up -d --build
```

2. On first start the `app` container will detect an empty `src` and run `composer create-project` into the mounted `src` directory automatically. Wait for logs to finish.

3. Copy `.env.docker` to `src/.env` (or edit `src/.env`) and run migrations:

```bash
docker compose exec app php artisan migrate
```

Point your browser to `http://localhost`.

**Running Artisan Commands**

- **Start stack:**

```bash
docker compose up -d
```

- **Run an artisan command in the running `app` container (recommended):**

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
# then inside the shell:
php artisan route:list
```

- **Run a command as root (for permission tasks):**

```bash
docker compose exec -u root app php artisan storage:link
```

- **Follow app logs (useful while Laravel auto-installs on first start):**

```bash
docker compose logs -f app
```

