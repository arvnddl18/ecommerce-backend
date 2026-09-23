# E-Commerce Platform — Technical Documentation

**Project Type:** Full-stack web application (portfolio project)  
**Author:** Arvin  
**Last Updated:** September 2026  

> 🧭 **Obsidian Memory Brain:** [[CORE_MEMORY]] · [[System_Architecture]] · [[Docker_Containerization]] · [[Data_Flow_and_Storage]] · [[Payment_Pipeline]] · [[CI_CD_and_Cloud_Infrastructure]]

---

## 1. Overview


This document describes the technical architecture, infrastructure, and technology decisions behind a full-stack e-commerce platform built to demonstrate production-grade engineering practices: containerization, automated CI/CD, secure payment processing, and cloud deployment.

The platform is designed to be:
- **Scalable** — stateless application layer, horizontally deployable
- **Portable** — fully containerized, runs identically across local, staging, and production environments
- **Cost-efficient** — built on free-tier and low-cost infrastructure without compromising production-readiness
- **Globally accessible** — served through a CDN for low-latency access from any region

---

## 2. Technology Stack Summary

| Layer | Technology | Purpose |
|---|---|---|
| Backend Framework | Laravel 11 (PHP 8.3) | REST API, business logic, authentication |
| Frontend Framework | React 18 (TypeScript) | Client-side UI and interactivity |
| Primary Database | PostgreSQL 16 | Persistent relational data storage |
| Cache / Session Store | Redis 7 | Caching, session storage, queue backend |
| Web Server | Nginx | Reverse proxy, request routing |
| Containerization | Docker / Docker Compose | Environment consistency, service isolation |
| CI/CD | GitHub Actions | Automated testing, build, and deployment |
| Container Registry | GitHub Container Registry (GHCR) | Docker image storage |
| Payments | Stripe (Checkout + Webhooks) | Payment processing |
| CDN / Security | Cloudflare | DNS, CDN, DDoS protection, SSL |
| Hosting | VPS (DigitalOcean / Hetzner) or Railway/Render (free tier) | Application hosting |
| Error Tracking | Sentry | Production error monitoring |
| Uptime Monitoring | Better Stack / UptimeRobot | Availability monitoring |

---

## 3. System Architecture

> 🗺️ **Architecture Memory:** [[System_Architecture]] · [[Docker_Containerization]]

### 3.1 High-Level Request Flow

```
Client (Browser)
      │
      ▼
Cloudflare (CDN, DDoS protection, SSL termination)
      │
      ▼
VPS / Cloud Host
      │
      ├── Nginx (reverse proxy, port 80/443)
      │        │
      │        ▼
      │   Laravel App Container (PHP-FPM)
      │        │
      │        ├──► PostgreSQL Container (persistent data)
      │        ├──► Redis Container (cache, sessions, queue)
      │        └──► Stripe API (external, payment processing)
      │
      └── React Frontend (served separately, static build)
```

### 3.2 Container Architecture

| Container | Base Image | Responsibility |
|---|---|---|
| `app` | `php:8.3-fpm-alpine` | Runs Laravel application code |
| `webserver` | `nginx:stable-alpine` | Routes HTTP requests to `app` |
| `db` | `postgres:16-alpine` | Persistent relational database |
| `redis` | `redis:7-alpine` | In-memory cache and session store |

All containers communicate over an internal Docker bridge network (`ecommerce_network`), with only the web server's port exposed externally.

---

## 4. Backend — Laravel

> 📑 **Decision Record:** [[ADR-001_Laravel_11_Headless_REST_API]]

**Framework:** Laravel 11
**Language:** PHP 8.3
**Architecture style:** API-only (Laravel as a REST API, decoupled from the frontend)

### Responsibilities
- Authentication and authorization (Laravel Sanctum for token-based API auth)
- Product, inventory, and order management
- Cart and checkout logic
- Stripe payment integration, including webhook handling
- Database migrations and schema versioning

### Key Practices Implemented
- Environment-based configuration (`.env`, never committed to version control)
- Database migrations for all schema changes (no manual SQL edits)
- Automated tests via PHPUnit
- Static analysis via Larastan (PHPStan for Laravel)

---

## 5. Frontend — React

**Framework:** React 18
**Language:** TypeScript
**Build tool:** Vite

### Responsibilities
- Product browsing, cart, and checkout UI
- Communicates with the Laravel API over authenticated REST endpoints
- Client-side state management for cart and session data

### SEO Considerations
Since a pure client-rendered SPA is weak for SEO, SEO-critical pages (product listings, product detail pages) are handled via server-side rendering or Inertia.js integration with Laravel, ensuring search engines can index product content directly.

---

## 6. Database Layer

> 🗄️ **Detailed Architecture:** [[Data_Flow_and_Storage]] · **ADRs:** [[ADR-002_PostgreSQL_16_over_MySQL]] · [[ADR-003_Redis_for_Caching_Sessions_and_Queues]]

### 6.1 PostgreSQL (Primary Data Store)
- Stores all persistent entities: users, products, orders, order items, payment records
- Chosen over MySQL for stronger constraint handling, JSON column support, and closer alignment with production systems used in industry
- Schema managed entirely through Laravel migrations for full version control

### 6.2 Redis (Cache & Session Layer)
- Reduces repeated database reads for frequently accessed data (product listings, cart state)
- Backs Laravel's session driver and queue system
- Deployed as a separate container, isolated from the primary datastore

---

## 7. Containerization — Docker

> 📦 **Detailed Architecture:** [[Docker_Containerization]] · **ADR:** [[ADR-004_Multi_Stage_Docker_Builds_for_Production]] · **Configs:** [[docker-compose.yml]] · [[docker-compose.prod.yml]]

### Purpose
Docker ensures the application runs identically across local development, CI, and production — eliminating environment-specific bugs.

### Structure
```
project-root/
├── docker-compose.yml
├── docker/
│   ├── php/Dockerfile        # Laravel application image
│   └── nginx/default.conf    # Reverse proxy configuration
```

### Key Practices
- Multi-service isolation (app, web server, database, cache each in separate containers)
- Named volumes for persistent Postgres data
- Environment variables injected at runtime, not baked into images
- Production images built with `--no-dev --optimize-autoloader` for performance

---

## 8. CI/CD — GitHub Actions

> 🔄 **Infrastructure Spec:** [[CI_CD_and_Cloud_Infrastructure]]

### Pipeline Stages

| Stage | Action |
|---|---|
| **Lint** | ESLint (frontend), PHP CS Fixer / Larastan (backend) |
| **Test** | PHPUnit test suite (backend), Jest/React Testing Library (frontend) |
| **Build** | Docker image build for `app` container |
| **Push** | Image pushed to GitHub Container Registry (GHCR) |
| **Deploy** | Automated deployment to production host on merge to `main` |

### Environment Strategy
- **Staging** and **production** environments are kept separate, with independent environment variables and database instances
- Secrets (Stripe keys, DB credentials) are stored in GitHub Actions Secrets, never hardcoded

---

## 9. Payments — Stripe

> 💳 **Pipeline Spec:** [[Payment_Pipeline]] · [[Stripe_Payment_Integration_and_Webhooks]]

### Integration Scope
- **Stripe Checkout / Elements** for the payment UI
- **Webhooks** for asynchronous event handling:
  - `payment_intent.succeeded`
  - `payment_intent.payment_failed`
  - `charge.refunded`
- **Idempotency keys** used on payment requests to prevent duplicate charges on retry

### Security
- No card data ever touches the application server — handled entirely by Stripe's hosted components
- Webhook signatures verified on receipt to prevent spoofed events

---

## 10. Infrastructure & Hosting

> ☁️ **Cloud Details:** [[CI_CD_and_Cloud_Infrastructure]] · [[ADR-005_Oracle_Cloud_Always_Free_Hosting]]

| Component | Provider | Tier |
|---|---|---|
| Backend/Frontend hosting | DigitalOcean / Hetzner VPS, or Railway/Render | Low-cost / free tier |
| Database | Self-hosted (Docker) or Supabase/Neon | Free tier available |
| Redis | Self-hosted (Docker) or Upstash | Free tier available |
| CDN & DNS | Cloudflare | Free tier |
| SSL/TLS | Let's Encrypt (via Cloudflare or Certbot) | Free |
| Domain | Registered domain (e.g. Namecheap) | ~$10–15/year |

### Why This Setup Qualifies as Production-Grade
- Infrastructure defined as code (`docker-compose.yml`, GitHub Actions YAML) rather than manual configuration
- Clear separation of staging and production environments
- Centralized secrets management
- CDN-backed global content delivery for low-latency access worldwide

---

## 11. Monitoring & Observability

| Concern | Tool |
|---|---|
| Application errors | Sentry |
| Uptime / availability | Better Stack or UptimeRobot |
| Container logs | `docker compose logs`, aggregated via host-level log rotation |

---

## 12. Security Practices

- All secrets and credentials managed via environment variables, excluded from version control (`.gitignore`)
- HTTPS enforced across all endpoints
- Stripe webhook signature verification
- Laravel Sanctum for API token authentication
- Database credentials scoped per environment (no shared credentials between staging/production)

---

## 13. SEO Strategy

- Server-side rendering or Inertia.js for SEO-critical pages
- Schema.org structured data (Product, Offer, AggregateRating) on product pages
- Sitemap.xml and robots.txt configuration
- Optimized Core Web Vitals via CDN caching and image optimization

---

## 14. Local Development Setup

```bash
# Clone repository
git clone <repo-url>
cd ecommerce-backend

# Configure environment
cp .env.example .env

# Build and start containers
docker compose up -d --build

# Run migrations
docker compose exec app php artisan migrate

# Access application
# API:      http://localhost:8000
# Frontend: http://localhost:5173 (Vite dev server)
```

---

## 15. Future Roadmap

- [ ] Product search via Meilisearch or Elasticsearch
- [ ] Horizontal scaling with a container orchestrator (Kubernetes / k3s) as a stretch goal
- [ ] Multi-region deployment for reduced global latency
- [ ] Automated database backups and disaster recovery plan
- [ ] Load testing and performance benchmarking

---

## 16. Summary

This project demonstrates a complete, professional web application lifecycle: from containerized local development, through automated testing and deployment pipelines, to a globally distributed, monitored production environment — built using free and low-cost infrastructure without sacrificing engineering rigor.
