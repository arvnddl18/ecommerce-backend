# Core Memory

Status: VERIFIED
Last Updated: 2026-09-23

## Project
- Name: E-Commerce Platform Backend
- Purpose: Production-grade, containerized e-commerce backend built with Laravel 11, PostgreSQL 16, Redis 7, and Stripe checkout/webhooks. Designed to showcase scalable cloud architecture, automated CI/CD pipelines, and zero-cost hosting on Oracle Cloud Always-Free tier.
- Current objective: Establish autonomous Obsidian memory brain, implement robust REST API endpoints (catalog, cart, checkout session, Stripe webhook handlers), and database models with tests.

## Current State
- Active feature: Obsidian Autonomous Memory Brain setup & initial architecture documentation consolidation.
- Current development phase: Foundation & API Architecture Phase.
- Important blockers: None. Development environment containerized and functional via Docker Compose.

## Technology
- Backend: Laravel 11, PHP 8.3 / 8.4, Laravel Sanctum (API Tokens), Laravel Boost (Agentic tooling)
- Frontend: React 18, TypeScript, Vite (Decoupled client consuming REST API)
- Primary Database: PostgreSQL 16 Alpine (Relational persistent storage, JSONB columns)
- Cache & Queue Broker: Redis 7 Alpine (Sessions, cache-aside, asynchronous queue worker)
- Web Server & Reverse Proxy: Nginx Stable Alpine (Port 80/443, FastCGI to PHP-FPM :9000, Gzip, static asset caching)
- Containerization: Docker, Docker Compose (multi-stage builds, non-root user, OPcache enabled)
- Payment Gateway: Stripe Checkout & Asynchronous Webhook verification
- Infrastructure / Hosting: Oracle Cloud Infrastructure (OCI) Always-Free Tier (Ampere A1 ARM64, 4 OCPU, 24 GB RAM), Cloudflare (CDN, SSL, DDoS protection)
- CI/CD: GitHub Actions (Lint with Pint/Larastan, PHPUnit test suites, GHCR Docker image publishing)

## Architecture
- Key architectural decisions:
  - Decoupled headless REST API authenticated via Bearer tokens ([[ADR-001_Laravel_11_Headless_REST_API]]).
  - PostgreSQL 16 for strict ACID compliance, relational integrity, and JSONB event payloads ([[ADR-002_PostgreSQL_16_over_MySQL]]).
  - Redis 7 for unified memory caching, session storage, and asynchronous background jobs ([[ADR-003_Redis_for_Caching_Sessions_and_Queues]]).
  - Multi-stage Docker builds discarding build-time tools (Node, Composer) from production images ([[ADR-004_Multi_Stage_Docker_Builds_for_Production]]).
  - Oracle Cloud Always-Free Tier for permanent $0/month full-stack hosting ([[ADR-005_Oracle_Cloud_Always_Free_Hosting]]).
- Important modules:
  - `docker/` (Dockerfile.dev, Dockerfile.prod, Nginx configurations)
  - `app/Http/Controllers/Api/` (Auth, Catalog, Cart, Checkout, Webhooks)
  - `app/Services/` (StripeCheckoutService, WebhookFulfillmentService)
  - `database/migrations/` (Schema versioning)
  - `tests/Feature/` (PHPUnit API endpoint feature tests)

## Permanent Constraints
- **Hierarchy of Truth:** Actual repository source code is ALWAYS authoritative over memory notes.
- **Minimum Token Usage:** Targeted retrieval only; never load the entire vault into context.
- **Security & Secrets:** NEVER store real credentials, Stripe secret keys, private keys, or passwords in memory. Only store environment variable names (e.g., `STRIPE_WEBHOOK_SECRET`).
- **Database Schema Management:** Zero manual SQL changes on production; all schema modifications MUST happen through versioned Laravel migrations.
- **Financial Precision:** Currency amounts MUST be handled as integers in the smallest currency unit (e.g. cents) or high-precision decimals to avoid floating-point errors.
- **Code Style & Quality:** Run `vendor/bin/pint --dirty --format agent` on modified PHP files; write PHPUnit feature tests rather than throwaway verification scripts.

## Important User Preferences
- Clean, decoupled API design with descriptive method and variable names.
- Zero-cost cloud infrastructure optimization (leveraging Oracle Always-Free 24 GB RAM and Cloudflare free tier).
- Safe and idempotent payment transactions (verifying Stripe webhook signatures, using idempotency keys).
- Clear and organized documentation linked with `[[WikiLinks]]`.

## Critical Decisions
- [[ADR-001_Laravel_11_Headless_REST_API]]
- [[ADR-002_PostgreSQL_16_over_MySQL]]
- [[ADR-003_Redis_for_Caching_Sessions_and_Queues]]
- [[ADR-004_Multi_Stage_Docker_Builds_for_Production]]
- [[ADR-005_Oracle_Cloud_Always_Free_Hosting]]
