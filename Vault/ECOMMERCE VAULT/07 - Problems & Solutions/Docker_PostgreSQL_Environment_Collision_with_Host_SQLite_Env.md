# Docker PostgreSQL Database Name Collision With Host SQLite .env

Status: VERIFIED
Last Updated: 2026-09-25
Tags: #problem-solution #docker #postgresql #sqlite #env-isolation #devops

## Problem
After bringing up the Docker Compose stack with `docker compose up -d --build`, attempting to execute migrations inside the application container:
```bash
docker compose exec app php artisan migrate --seed
```
failed with the error:
```text
SQLSTATE[08006] [7] connection to server at "db" (172.18.0.2), port 5432 failed: FATAL: database "ecommerce" does not exist
```

---

## Root Cause
1. **Docker Compose Automatic `.env` Interpolation**:
   - Docker Compose automatically reads `.env` located in the project root to interpolate `${VAR}` syntax in `docker-compose.yml`.
2. **Host `.env` Configured for SQLite**:
   - The host workspace `.env` is configured for local SQLite:
     ```env
     DB_CONNECTION=sqlite
     DB_DATABASE=database/database.sqlite
     ```
3. **Variable Name Collision in `docker-compose.yml`**:
   - The `db` service definition in `docker-compose.yml` used:
     ```yaml
     environment:
       POSTGRES_DB: ${DB_DATABASE:-ecommerce}
     ```
   - Because `DB_DATABASE` was already set in `.env` as `database/database.sqlite`, Docker Compose evaluated `${DB_DATABASE:-ecommerce}` to `database/database.sqlite` instead of the fallback `ecommerce`!
   - As a result, the PostgreSQL container initialized an empty database literally named `database/database.sqlite`.
4. **App Container Reads `.env.docker`**:
   - The `app` container loaded `.env.docker` where `DB_DATABASE=ecommerce`.
   - When Laravel attempted to connect to `ecommerce`, PostgreSQL rejected the connection because the database did not exist.

---

## Solution & Remediation
1. **Created Missing `ecommerce` Database in PostgreSQL**:
   Connected via `psql` to create the target database:
   ```bash
   docker compose exec -T db psql -U ecommerce_user -d postgres -c "CREATE DATABASE ecommerce;"
   ```
2. **Decoupled Container Env in `docker-compose.yml`**:
   Renamed environment variables in `docker-compose.yml` to prevent accidental host `.env` variable bleed:
   ```yaml
   db:
     image: postgres:16-alpine
     container_name: ecommerce-db
     environment:
       POSTGRES_DB: ${DOCKER_POSTGRES_DB:-ecommerce}
       POSTGRES_USER: ${DOCKER_POSTGRES_USER:-ecommerce_user}
       POSTGRES_PASSWORD: ${DOCKER_POSTGRES_PASSWORD:-secret123}
     healthcheck:
       test: ["CMD-SHELL", "pg_isready -U ${DOCKER_POSTGRES_USER:-ecommerce_user} -d ${DOCKER_POSTGRES_DB:-ecommerce}"]
   ```
3. **Ran Migrations & Seeders**:
   Successfully executed all migrations and seeders:
   ```bash
   docker compose exec -T app php artisan migrate --seed
   ```
4. **Verified Container API**:
   Verified HTTP response on `http://localhost:8000/api/v1/products` returning seeded products from PostgreSQL.

---

## Related Links
- [[CORE_MEMORY]]
- [[Docker_Containerization]]
- [[Docker_Local_Development_and_Testing_Workflow]]
- [[GitHub_Push_Protection_and_Environment_Secrets_Leak_Remediation]]
