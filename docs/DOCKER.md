# Docker setup

Local development stack: **PHP 8.3-FPM**, **Nginx**, **MySQL 8.4**, **Redis 7**. Optional **PostgreSQL 16** profile.

## Prerequisites

- Docker Compose v2
- OpenSSL (for JWT key generation)

## Quick start

```bash
cp .env.example .env

mkdir -p config/jwt
openssl genpkey -algorithm rsa -pkeyopt rsa_keygen_bits:4096 -out config/jwt/private.pem
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout

docker compose up -d --build
docker compose exec app composer install
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec app php bin/console doctrine:fixtures:load --no-interaction
```

## Services

| Service | Image | Host access | Internal |
|---------|-------|-------------|----------|
| `nginx` | nginx:1.27-alpine | http://localhost:8080 | port 80 |
| `app` | Custom PHP 8.3-FPM | — | `app:9000` (FastCGI) |
| `mysql` | mysql:8.4 | `127.0.0.1:33060` | `mysql:3306` |
| `redis` | redis:7-alpine | `127.0.0.1:63790` | `redis:6379` |
| `postgres` | postgres:16-alpine (profile) | `127.0.0.1:54320` | `postgres:5432` |

## Environment inside containers

The `app` service sets database and Redis URLs for the Compose network (`mysql`, `redis` hostnames). When running Symfony commands **from your host** (without Docker), use the mapped ports in `.env`:

```dotenv
DATABASE_URL="mysql://marketplace:marketplace@127.0.0.1:33060/marketplace?serverVersion=8.0&charset=utf8mb4"
REDIS_DSN=redis://127.0.0.1:63790
MESSENGER_TRANSPORT_DSN=redis://127.0.0.1:63790/messages
```

`JWT_PASSPHRASE` in `docker-compose.yml` defaults to `dev_passphrase_change_me` — align it with `.env` or override via shell export.

## Common commands

```bash
# Start / stop
docker compose up -d
docker compose down

# Logs
docker compose logs -f app nginx

# Symfony console
docker compose exec app php bin/console about
docker compose exec app php bin/console doctrine:migrations:status

# Messenger consumer (async notifications)
docker compose exec app php bin/console messenger:consume async -vv

# PHPUnit (inside container)
docker compose exec app ./vendor/bin/phpunit -c phpunit.dist.xml
```

## PostgreSQL profile

```bash
docker compose --profile postgres up -d postgres
```

Update `DATABASE_URL` in `.env` to the PostgreSQL DSN (see `.env.example` comments), then run migrations from the `app` container.

## PHP image

Built from `docker/php/Dockerfile`:

- Extensions: `pdo_mysql`, `zip`, `opcache`, `redis`
- Composer 2 included
- Project mounted at `/var/www/html`

Nginx config: `docker/nginx/default.conf` — front controller `public/index.php`.

## Dev credentials

Docker Compose uses **development-only** database passwords (`marketplace` / `rootsecret`). Never reuse these in production.

Demo API accounts (after fixtures): see README — password `DemoPass2026!`.

## Troubleshooting

| Issue | Fix |
|-------|-----|
| `502` from Nginx | Wait for `app` container; check `docker compose logs app` |
| JWT errors | Ensure `config/jwt/*.pem` exist and `JWT_PASSPHRASE` matches |
| DB connection refused from host | Use port `33060`, not `3306` |
| Redis connection from host | Use port `63790`, not `6379` |
| Permission errors on `var/` | `docker compose exec app chown -R www-data:www-data var` |

## Without Docker

```bash
composer install
cp .env.example .env
# configure DATABASE_URL, REDIS_DSN, generate JWT keys
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
symfony server:start
```

Tests use SQLite and in-memory Messenger — no Docker required for `./vendor/bin/phpunit`.
