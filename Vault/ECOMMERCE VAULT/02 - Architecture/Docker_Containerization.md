# Docker Containerization

Status: VERIFIED (Docker Compose & Dockerfile Specs) · Local & Production Stacks
Last Updated: 2026-09-24
Tags: #architecture #docker #devops #verified

> [!NOTE]
> The Docker container specs (`docker-compose.yml`, `docker-compose.prod.yml`, `docker/php/Dockerfile`, `docker/php/Dockerfile.prod`) are fully defined. Active local development can run either natively on Windows (PHP 8.4 CLI + SQLite) or fully containerized via Docker Compose (PHP 8.4-FPM + PostgreSQL 16 + Redis 7 + Nginx + Queue Worker + Scheduler).

## 1. Multi-Container Composition
The stack is partitioned into discrete services running on an internal Docker bridge network (`ecommerce-network`).

| Container Service | Base Image | Role | Exposed Port (Local) | Healthcheck |
|---|---|---|---|---|
| `webserver` | `nginx:alpine` | HTTP reverse proxy, static asset delivery | `8000:80` | Via `app` dependency |
| `app` | `php:8.4-fpm-alpine` | Core Laravel application runtime (FastCGI) | Internal `:9000` | Requires `db` & `redis` healthy |
| `db` | `postgres:16-alpine` | Primary relational database (JSONB, strict types) | `5432:5432` | `pg_isready` |
| `redis` | `redis:7-alpine` | Cache, sessions, queue backend | `6379:6379` | `redis-cli ping` |
| `worker` | `php:8.4-fpm-alpine` | Asynchronous queue worker (`queue:work redis`) | None | Requires `app`, `db`, `redis` |
| `scheduler` | `php:8.4-fpm-alpine` | Cron scheduler runner (`schedule:work`) | None | Requires `app`, `db` |

## 2. Dev vs. Production Separation
- **Local Dev (`docker-compose.yml`):**
  - Uses volume bind mounts (`.:/var/www/html`) for instantaneous reflection of code changes without image rebuilds.
  - Automatically loads `.env.docker` via `env_file`, preventing collisions with native host `.env`.
  - Runs `docker/php/entrypoint.sh` to ensure `storage/` and `bootstrap/cache/` directories exist and have proper permissions on Windows WSL2 bind mounts.
  - Exposes database (`5432`) and Redis (`6379`) to host for direct GUI inspection (e.g. TablePlus, DBeaver, RedisInsight).
- **Production (`docker-compose.prod.yml`):**
  - Employs multi-stage builds (`docker/php/Dockerfile.prod`) where Node 20 compiles Vite assets and Composer installs `--no-dev` dependencies.
  - Files copied directly with `--chown=www-data:www-data`, zero host bind mounts.
  - Production OPcache enabled (`docker/php/opcache.ini`).
  - Only ports 80/443 exposed via `webserver`.

## 3. Related Links
- [[CORE_MEMORY]]
- [[System_Architecture]]
- [[ADR-004_Multi_Stage_Docker_Builds_for_Production]]
- [[Docker_Windows_WSL2_and_Permissions]]
- [[Docker_Production_OPcache_Optimization]]
- [[Docker_Local_Development_and_Testing_Workflow]]
