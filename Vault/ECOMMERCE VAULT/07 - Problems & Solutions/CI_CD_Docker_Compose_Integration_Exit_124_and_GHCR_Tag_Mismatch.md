# CI/CD Docker Compose Integration Exit 124 & GHCR Tag Sync

Status: VERIFIED
Last Updated: 2026-09-25
Tags: #problem-solution #ci-cd #docker #docker-compose #github-actions #ghcr #trivy #testing

## Problem
In GitHub Actions CI/CD Pipeline run `#6` (commit `9ffe1d2`), the workflow failed on the `Docker Compose Integration Tests` job after running for 3m 53s with:
```text
Process completed with exit code 124.
```
While `Code Style & Static Analysis` (26s), `Automated PHPUnit Feature Tests` (55s), and `Build Dev Docker Image` (3m 42s) all passed, the integration job failed during `Wait for services to be healthy`.

Additionally, the downstream jobs `build-and-push` and `scan` contained latent breaking issues:
1. Docker image tagging in `metadata-action` produced only short SHA (`sha-9ffe1d2`), while `trivy-action` scanned the 40-character long SHA (`sha-${{ github.sha }}`), causing image manifest 404s.
2. Trivy lacked GHCR registry credentials for private repository image scanning.

---

## Root Causes

1. **Missing Vendor Dependencies on Host Mount in Integration Job**:
   - `docker-compose.yml` mounts the project root directly into the application container: `volumes: - ./:/var/www/html`.
   - On GitHub Actions runners, checkout provides a clean repository clone where `vendor/` and `node_modules/` do not exist (`.gitignore`).
   - The `integration` job executed `docker compose up -d --build` without running `composer install` or `npm ci && npm run build` on the host first.
   - When the healthcheck ran `docker compose exec -T app php artisan about`, `artisan` failed immediately with:
     ```text
     Fatal error: Failed opening required '/var/www/html/vendor/autoload.php'
     ```
   - The script had `timeout 60 bash -c 'until docker compose exec -T app php artisan about >/dev/null 2>&1; do sleep 2; done'`. Because `vendor/autoload.php` never appeared, the command failed on every iteration until the 60-second timer elapsed, exiting with GNU timeout exit code `124`.

2. **Missing Environment Configuration File**:
   - `docker-compose.yml` and Laravel Artisan operations in the container depend on container-aware environment settings (`DB_HOST=db`, `REDIS_HOST=redis`). The runner lacked a `.env` file mapped to `.env.docker`.

3. **Missing Vite Build Manifest for Feature Tests**:
   - `tests/Feature/ExampleTest.php` calls `$this->get('/')`, which renders `resources/views/app.blade.php`.
   - Without pre-built assets in `public/build/manifest.json`, Laravel throws `ViteException: Unable to locate file in Vite manifest`.

4. **Missing SQLite Driver in Dev Dockerfile**:
   - `docker/php/Dockerfile` omitted `sqlite-dev` and `pdo_sqlite`. If PHPUnit runs inside the container without passing PostgreSQL connection overrides, it falls back to `phpunit.xml` (`:memory:` SQLite) and crashes with missing driver errors.

5. **Downstream GHCR Tag Mismatch & Security Scan Failures**:
   - `build-and-push` generated tags with `type=sha,prefix=sha-,format=short` (`sha-9ffe1d2`).
   - `scan` job queried `ghcr.io/${{ github.repository }}:sha-${{ github.sha }}` using the full 40-character SHA, which didn't match the pushed tag.
   - `trivy-action` attempted to pull the container without registry authentication.

---

## Solution & Remediation

1. **Full Environment Pre-Seeding in `ci.yml` Integration Job**:
   Updated `.github/workflows/ci.yml` `integration` job to set up PHP 8.4, Node 22, install dependencies, compile Vite assets, and copy `.env.docker` before invoking Docker Compose:
   ```yaml
   - name: Setup PHP 8.4
     uses: shivammathur/setup-php@v2
     with:
       php-version: 8.4
       extensions: mbstring, bcmath, pdo, pdo_pgsql, pdo_sqlite, redis
       coverage: none

   - name: Setup Node.js 22
     uses: actions/setup-node@v4
     with:
       node-version: 22
       cache: 'npm'

   - name: Install Composer Dependencies
     run: composer install --prefer-dist --no-progress --no-interaction

   - name: Install NPM Dependencies & Build Vite Assets
     run: |
       npm ci
       npm run build

   - name: Prepare Docker Environment File
     run: cp .env.docker .env
   ```

2. **Diagnostic-Rich Healthcheck Loop**:
   Replaced silent `timeout 60` with a resilient retry loop that dumps container logs if services do not report ready:
   ```yaml
   - name: Wait for services to be healthy
     run: |
       docker compose ps
       for i in $(seq 1 30); do
         if docker compose exec -T app php artisan about >/dev/null 2>&1; then
           echo "Laravel application is responsive and database/cache connections verified!"
           docker compose exec -T app php artisan about
           exit 0
         fi
         echo "Waiting for services to become responsive... ($i/30)"
         sleep 2
       done
       echo "Services failed to become healthy within 60s. Printing container status and logs:"
       docker compose ps
       docker compose logs
       exit 1
   ```

3. **Explicit Container Database Overrides for Integration Tests**:
   Ensured the integration test suite runs against the live Docker PostgreSQL and Redis services:
   ```yaml
   - name: Run integration tests
     run: |
       docker compose exec -T \
         -e DB_CONNECTION=pgsql \
         -e DB_HOST=db \
         -e DB_PORT=5432 \
         -e DB_DATABASE=ecommerce \
         -e DB_USERNAME=ecommerce_user \
         -e DB_PASSWORD=secret123 \
         -e CACHE_STORE=redis \
         -e QUEUE_CONNECTION=redis \
         -e REDIS_HOST=redis \
         -e REDIS_CLIENT=predis \
         app php artisan test --compact
   ```

4. **Added `sqlite-dev` & `pdo_sqlite` to `docker/php/Dockerfile`**:
   Ensured both PostgreSQL and SQLite drivers are compiled in the development container image.

5. **Aligned `docker/php/Dockerfile.prod` with PHP 8.4 & Production Extensions**:
   - Updated base image from `php:8.3-fpm-alpine` to `php:8.4-fpm-alpine`.
   - Included `tsconfig*.json` in Vite asset build stage.
   - Installed `icu-dev`, `libjpeg-turbo-dev`, `freetype-dev`, and enabled `gd` and `intl` extensions.

6. **Synchronized Docker Tags and Authenticated Trivy**:
   - Added `type=sha,prefix=sha-,format=long` alongside `format=short` in `docker/metadata-action@v5` (as `docker/metadata-action` accepts `short` and `long`, rejecting `full` with an `Invalid format` error).
   - Added Docker login and `TRIVY_USERNAME` / `TRIVY_PASSWORD` credentials in `scan` job.

---

## Related Links
- [[CORE_MEMORY]]
- [[Docker_Containerization]]
- [[Docker_Local_Development_and_Testing_Workflow]]
- [[GitHub_Actions_CI_CD_Automation_and_Zero_Config_Fallback]]
- [[Docker_PostgreSQL_Environment_Collision_with_Host_SQLite_Env]]
- [[CI_CD_NPM_Lockfile_Sync_and_Static_Analysis_Fixes]]
