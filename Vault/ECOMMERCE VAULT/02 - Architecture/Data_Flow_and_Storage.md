# Data Flow and Storage

Status: VERIFIED (Docker / Production Spec) · SQLite / Native PHP (Local Runtime)
Last Updated: 2026-09-23
Tags: #architecture #database #redis #calibrated

## 1. Request Data Flow
> [!NOTE]
> The active local development runtime executes natively via `php artisan serve` on port 8000 using **SQLite** (`database/database.sqlite`), file sessions, and **synchronous queues** (`QUEUE_CONNECTION=sync`). The steps below represent the fully verified containerized **Docker / Production Target Architecture** (`docker-compose.yml`, `docker-compose.prod.yml`).

1. **API Requests:** Browser sends HTTPS request with Sanctum bearer token to Nginx.
2. **FastCGI Dispatch:** Nginx routes request to PHP-FPM in `app` container.
3. **Session / Cache Inspection:** Laravel queries Redis for cached catalog data or rate limits (or SQLite/cache file locally).
4. **Transactional Storage:** If writing (e.g. creating order, updating profile), the database (SQLite locally / PostgreSQL in Docker) performs ACID operations.
5. **Background Offloading:** Post-commit actions (emails, inventory recalculation, webhook processing) are handled synchronously in local runtime or offloaded to the Redis queue worker in Docker.

## 2. Storage Strategy
- **Local Dev Runtime:** Native filesystem SQLite database at `database/database.sqlite` with file-based cache and session storage.
- **Production Target (Docker Compose):**
  - **PostgreSQL Volume:** Named Docker volume `postgres_data` mounts into `/var/lib/postgresql/data` ensuring persistent storage across container recreation.
  - **Redis Volume:** Named Docker volume `redis_data` with append-only file (AOF) persistence enabled.

## 3. Related Links
- [[CORE_MEMORY]]
- [[System_Architecture]]
- [[ADR-002_PostgreSQL_16_over_MySQL]]
- [[ADR-003_Redis_for_Caching_Sessions_and_Queues]]
