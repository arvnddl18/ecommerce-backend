# Data Flow and Storage

Status: VERIFIED
Last Updated: 2026-09-23
Tags: #architecture #database #redis

## 1. Request Data Flow
1. **API Requests:** Browser sends HTTPS request with Sanctum bearer token to Nginx.
2. **FastCGI Dispatch:** Nginx routes request to PHP-FPM in `app` container.
3. **Session / Cache Inspection:** Laravel queries Redis for cached catalog data or rate limits.
4. **Transactional Storage:** If writing (e.g. creating order, updating profile), PostgreSQL performs ACID operations with row-level locks.
5. **Background Offloading:** Post-commit actions (emails, inventory recalculation) are pushed onto Redis queue and consumed by `worker`.

## 2. Storage Strategy
- **PostgreSQL Volume:** Named Docker volume `postgres_data` mounts into `/var/lib/postgresql/data` ensuring persistent storage across container recreation.
- **Redis Volume:** Named Docker volume `redis_data` with append-only file (AOF) persistence enabled.

## 3. Related Links
- [[CORE_MEMORY]]
- [[System_Architecture]]
- [[ADR-002_PostgreSQL_16_over_MySQL]]
- [[ADR-003_Redis_for_Caching_Sessions_and_Queues]]
