# Docker Local Development and Testing Workflow

Status: VERIFIED
Last Updated: 2026-09-24
Tags: #docker #devops #testing #local-development #verified

> [!NOTE]
> This guide details the complete flow of Docker containerization for local development and testing, explaining container interactions, request lifecycles, Windows WSL2 considerations, and command execution.

---

## 1. System Topology & Request Flow

```
[ Browser / API Client (Postman, curl) ]
                  │  http://localhost:8000
                  ▼
         ┌──────────────────┐
         │ ecommerce-webserver │ (Nginx :80 -> :8000)
         └────────┬─────────┘
                  │ FastCGI proxy (:9000)
                  ▼
         ┌──────────────────┐
         │  ecommerce-app   │ (PHP 8.4-FPM)
         └────────┬─────────┘
                  ├─── SQL Queries (:5432) ───► [ ecommerce-db (PostgreSQL 16) ]
                  │
                  └─── Queues/Cache (:6379) ──► [ ecommerce-redis (Redis 7) ]
                                                        ▲
         ┌──────────────────┐                           │
         │ ecommerce-worker │ ─── Polls Jobs (Redis) ───┘
         └──────────────────┘     (Processes emails, webhooks)
                                                        
         ┌────────────────────┐
         │ecommerce-scheduler │ ─── Runs Schedule Every Minute
         └────────────────────┘
```

### Request Lifecycle Step-by-Step
1. **Client Request**: You send an HTTP request to `http://localhost:8000/api/v1/products`.
2. **Reverse Proxy (Nginx)**: The `webserver` container receives traffic on port 80 (mapped to host 8000). For static assets (`.css`, `.js`, images), Nginx serves them directly from `/var/www/html/public`.
3. **FastCGI Delegation**: For PHP endpoints, Nginx passes the request via FastCGI over the internal bridge network (`ecommerce-network`) to `app:9000`.
4. **Application Execution**: The `ecommerce-app` container running PHP 8.4-FPM invokes `public/index.php`, boots Laravel, resolves routing, middleware, controllers, and services.
5. **Persistence & Cache**:
   - Database operations query `db:5432` (PostgreSQL 16).
   - Session, cache, and queue dispatches connect to `redis:6379`.
6. **Asynchronous Processing**: When a job is dispatched (e.g. `SendOrderConfirmationEmail`), it is pushed onto the Redis queue. The `ecommerce-worker` container continuously polls Redis and processes it in the background.

---

## 2. Windows WSL2 & Bind Mount Synchronization

- In local development, the repository on the Windows host (`C:\arvincodework\ecommerce-backend`) is mounted into the containers at `/var/www/html`:
  ```yaml
  volumes:
    - ./:/var/www/html
  ```
- **Instant Code Updates**: Any edit you make in your IDE on Windows is immediately reflected inside the container. You **do not** need to rebuild the Docker image after changing PHP, Blade, or config files.
- **Permission Auto-Healing**: Alpine Linux runs under POSIX permissions. Windows bind mounts can sometimes create permission issues in `storage/logs` or `bootstrap/cache`. Our `docker/php/entrypoint.sh` automatically creates and grants write permissions on startup.

---

## 3. Environment Isolation (`.env` vs `.env.docker`)

- **Host Native (`.env`)**: Configured for local lightweight testing using SQLite (`database/database.sqlite`) and `sync` queues.
- **Containerized Stack (`.env.docker`)**: Loaded automatically by `docker-compose.yml` via `env_file: .env.docker`. Uses PostgreSQL (`DB_HOST=db`, `DB_PORT=5432`) and Redis (`REDIS_HOST=redis`, `QUEUE_CONNECTION=redis`).

---

## 4. Operational Commands Cheat Sheet

| Action | Command | Purpose |
|---|---|---|
| **Build & Start** | `docker compose up -d --build` | Builds images, creates network/volumes, starts services in background |
| **Check Status** | `docker compose ps` | Displays status and health of all 6 containers |
| **View Logs** | `docker compose logs -f app` | Streams real-time logs from application container |
| **Tail All Logs** | `docker compose logs -f` | Streams combined logs across all containers |
| **Run Migrations** | `docker compose exec app php artisan migrate --seed` | Migrates and seeds PostgreSQL database |
| **Run Tests** | `docker compose exec app php artisan test` | Executes PHPUnit test suite inside container |
| **Interactive Shell** | `docker compose exec app sh` | Enters container shell for debugging |
| **Tinker Session** | `docker compose exec app php artisan tinker` | Opens Laravel Tinker in container context |
| **Stop Stack** | `docker compose stop` | Halts containers without deleting data |
| **Tear Down** | `docker compose down` | Stops and removes containers and networks |
| **Wipe Data** | `docker compose down -v` | Destroys containers and persistent data volumes |

---

## 5. Related Links
- [[CORE_MEMORY]]
- [[Docker_Containerization]]
- [[Docker_Windows_WSL2_and_Permissions]]
- [[ADR-004_Multi_Stage_Docker_Builds_for_Production]]
