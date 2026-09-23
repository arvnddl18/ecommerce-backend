# Docker Containerization

Status: VERIFIED
Last Updated: 2026-09-23
Tags: #architecture #docker #devops

## 1. Multi-Container Composition
The stack is partitioned into discrete services running on an internal Docker bridge network (`ecommerce_network`).

| Container Service | Base Image | Role | Exposed Port |
|---|---|---|---|
| `webserver` | `nginx:stable-alpine` | Reverse proxy & static assets | `8000:80` (dev) / `80, 443` (prod) |
| `app` | `php:8.3-fpm-alpine` | Application runtime | None (internal `:9000`) |
| `db` | `postgres:16-alpine` | Relational database | `5432:5432` (dev) / internal (prod) |
| `redis` | `redis:7-alpine` | Cache, sessions, queue backend | `6379:6379` (dev) / internal (prod) |
| `worker` | `php:8.3-fpm-alpine` | Asynchronous queue worker | None |
| `scheduler` | `php:8.3-fpm-alpine` | Task schedule runner | None |

## 2. Dev vs. Production Separation
- **Local Dev (`docker-compose.yml`):** Uses volume bind mounts (`.:/var/www/html`) for instantaneous reflection of code changes. Database and Redis ports are exposed for direct desktop GUI management.
- **Production (`docker-compose.prod.yml`):** Employs multi-stage builds (`Dockerfile.prod`) where Vite compiles assets and Composer installs `--no-dev` dependencies. Only `webserver` ports (80/443) are exposed externally. OPcache is enabled.

## 3. Related Links
- [[CORE_MEMORY]]
- [[System_Architecture]]
- [[ADR-004_Multi_Stage_Docker_Builds_for_Production]]
- [[Docker_Production_OPcache_Optimization]]
