# ADR-004: Multi-Stage Docker Builds for Production

Status: VERIFIED
Date: 2026-09-23
Tags: #adr #docker #devops

## Context
Deploying development runtimes (Node.js, NPM, Composer, dev dependencies) into production Docker containers bloats image sizes, creates security vulnerabilities, and degrades boot times.

## Decision
Implement a multi-stage Docker build (`docker/php/Dockerfile.prod`) featuring:
1. **Frontend stage (`node:20-alpine`):** Compiles Vite production assets.
2. **Composer stage (`composer:2`):** Installs PHP production dependencies with `--no-dev --optimize-autoloader`.
3. **Runtime stage (`php:8.3-fpm-alpine`):** Copies only compiled artifacts into a lean image with OPcache enabled, running as non-root `www-data`.

## Rationale & Consequences
- **Minimal Image Size:** Final runtime image is ~150MB instead of >1GB.
- **Zero Host Tool Dependencies:** Host VM does not require Node.js or Composer installed.
- **Enhanced Security:** Discards build toolchains and development packages from the attack surface.

## Related Links
- [[CORE_MEMORY]]
- [[Docker_Containerization]]
- [[Docker_Production_OPcache_Optimization]]
