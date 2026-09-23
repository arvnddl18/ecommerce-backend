# Core Memory

Status: VERIFIED (Full Multi-Vendor Apparel Marketplace Operational)
Last Updated: 2026-09-23
Author: Arvin

## Project
- Name: Apparel E-Commerce Marketplace
- Purpose: Multi-vendor apparel e-commerce marketplace (Shopify-style merchant model under a centralized platform). Empowers local and branded apparel sellers to manage listings, variants, and payouts, while providing buyers (aged 16+) with a fast, visually engaging, mobile-first shopping experience.
- Current objective: Multi-vendor apparel marketplace implementation fully deployed and verified across database, REST API, seeders, and React 18 frontend.

## Current State
- Active feature: Multi-Vendor Apparel Marketplace with Storefront, Seller Studio, and Admin Command Center operational.
- Current development phase: Production Ready — Features, tests, and assets verified.
- Important blockers: None. 37 automated PHPUnit feature tests passing (152 assertions), Pint formatted, Larastan clean, Vite production bundle compiled.

## Technology
- Backend: Laravel 11, PHP 8.3 / 8.4, Laravel Sanctum (API Tokens), Laravel Boost (Agentic tooling)
- Frontend: React 18, TypeScript, Vite, Tailwind CSS (Three unified surfaces: Storefront, Seller Dashboard, Admin Panel)
- UI Animation & Motion: Framer Motion (micro-interactions, page transitions, shimmers) + Swiper.js (touch-friendly carousels and zoom) ([[ADR-007_Framer_Motion_and_Swiper_for_Frontend_Experience]])
- Primary Database: PostgreSQL 16 Alpine (Relational persistent storage, JSONB columns, variant matrix)
- Cache & Queue Broker: Redis 7 Alpine (Sessions, cache-aside, asynchronous queue worker)
- Web Server & Reverse Proxy: Nginx Stable Alpine (Port 80/443, FastCGI to PHP-FPM :9000, Gzip, static asset caching)
- Containerization: Docker, Docker Compose (multi-stage builds, non-root user, OPcache enabled)
- Payment Gateway: Stripe Connect (multi-seller payout splitting, vendor connected accounts) & Webhooks ([[ADR-006_Stripe_Connect_for_Multi_Seller_Payouts]])
- Infrastructure / Hosting: Oracle Cloud Infrastructure (OCI) Always-Free Tier, Cloudflare (CDN, SSL, DDoS protection)
- CI/CD: GitHub Actions (Lint with Pint/Larastan, PHPUnit test suites, GHCR Docker image publishing)

## Architecture
- Key architectural decisions:
  - Decoupled headless REST API authenticated via Bearer tokens ([[ADR-001_Laravel_11_Headless_REST_API]]).
  - PostgreSQL 16 for strict ACID compliance, relational integrity, and JSONB event payloads ([[ADR-002_PostgreSQL_16_over_MySQL]]).
  - Redis 7 for unified memory caching, session storage, and asynchronous background jobs ([[ADR-003_Redis_for_Caching_Sessions_and_Queues]]).
  - Multi-stage Docker builds discarding build-time tools from production images ([[ADR-004_Multi_Stage_Docker_Builds_for_Production]]).
  - Oracle Cloud Always-Free Tier for permanent $0/month full-stack hosting ([[ADR-005_Oracle_Cloud_Always_Free_Hosting]]).
  - Stripe Connect for multi-vendor checkout and seller payout splitting ([[ADR-006_Stripe_Connect_for_Multi_Seller_Payouts]]).
  - Framer Motion and Swiper.js for rich micro-interactions and mobile touch galleries ([[ADR-007_Framer_Motion_and_Swiper_for_Frontend_Experience]]).
- Important modules:
  - `docker/` (Dockerfile.dev, Dockerfile.prod, Nginx configurations)
  - `app/Http/Controllers/Api/` (Auth, Catalog, Cart, Checkout, Webhooks, Seller, Admin)
  - `app/Services/` (StripeCheckoutService, WebhookFulfillmentService, CartService)
  - `database/migrations/` (PostgreSQL schema versioning)
  - `tests/Feature/` (PHPUnit API endpoint feature tests)

## Permanent Constraints
- **Hierarchy of Truth:** Actual repository source code is ALWAYS authoritative over memory notes.
- **Minimum Token Usage:** Targeted retrieval only; never load the entire vault into context.
- **Security & Secrets:** NEVER store real credentials, Stripe secret keys, private keys, or passwords in memory. Only store environment variable names (e.g., `STRIPE_WEBHOOK_SECRET`).
- **Database Schema Management:** Zero manual SQL changes on production; all schema modifications MUST happen through versioned Laravel migrations.
- **Financial Precision:** Currency amounts MUST be handled as integers in the smallest currency unit (cents) or high-precision decimals to avoid floating-point errors.
- **Code Style & Quality:** Run `vendor/bin/pint --dirty --format agent` on modified PHP files; write PHPUnit feature tests rather than throwaway verification scripts.

## Important User Preferences
- Visual-first presentation with generous white space and minimal palettes.
- Mobile-first approach optimized for demographic 16+ shoppers.
- Frictionless checkout completable in 3 steps or fewer.
- Ethical psychological purchase triggers (accurate scarcity counters, verified social proof).
- Strict separation of motion tools: Framer Motion for micro-interactions, Swiper.js for carousels.
- Boutique editorial aesthetic: "The Rail & The Rack" design system rejecting AI-generated template clichés ([[Frontend_Design_System_Architecture]], [[ADR-008_The_Rail_and_The_Rack_Storefront_Design_System]]).

## Critical Decisions & Architecture
- [[ADR-001_Laravel_11_Headless_REST_API]]
- [[ADR-002_PostgreSQL_16_over_MySQL]]
- [[ADR-003_Redis_for_Caching_Sessions_and_Queues]]
- [[ADR-004_Multi_Stage_Docker_Builds_for_Production]]
- [[ADR-005_Oracle_Cloud_Always_Free_Hosting]]
- [[ADR-006_Stripe_Connect_for_Multi_Seller_Payouts]]
- [[ADR-007_Framer_Motion_and_Swiper_for_Frontend_Experience]]
- [[ADR-008_The_Rail_and_The_Rack_Storefront_Design_System]]
- [[Frontend_Design_System_Architecture]]
- [[Apparel_Marketplace_Requirements_and_Design_Plan]]
