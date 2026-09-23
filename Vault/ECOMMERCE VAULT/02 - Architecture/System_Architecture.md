# System Architecture

Status: VERIFIED
Last Updated: 2026-09-23
Tags: #architecture #system-design

## 1. High-Level Topology

```mermaid
graph TD
    Client[Browser / React 18 SPA] -->|HTTPS :443| Cloudflare[Cloudflare CDN & Edge SSL]
    Cloudflare -->|Port 80/443| Nginx[Webserver: Nginx Alpine]
    Nginx -->|FastCGI :9000| App[App: Laravel 11 PHP-FPM]
    Worker[Worker: php artisan queue:work] -->|Redis Queues| Redis[(Redis 7)]
    Scheduler[Scheduler: php artisan schedule:work] -->|Cron Runs| App
    App -->|SQL :5432| DB[(PostgreSQL 16)]
    App -->|Cache & Queues| Redis
    App -->|REST API| Stripe[Stripe API & Hosted Checkout]
    Stripe -->|Webhooks :443| Nginx
```

## 2. Layer Responsibilities
- **Edge Layer (Cloudflare):** Terminating SSL, caching static assets, rate limiting, and DDoS filtering.
- **Web Proxy Layer (Nginx):** Direct reverse proxy, serving gzip-compressed assets, routing API traffic to PHP-FPM.
- **Application Layer (Laravel 11):** Stateless REST API handling auth, inventory logic, order calculation, and Stripe integration.
- **Worker Layer (PHP-FPM CLI):** Continuous background queue consumer for webhooks, emails, and sync tasks.
- **Persistent Data Layer (PostgreSQL 16):** ACID transactional storage with named volume persistence.
- **In-Memory Layer (Redis 7):** Key-value cache, session store, and queue message broker.

## 3. Related Links
- [[CORE_MEMORY]]
- [[Docker_Containerization]]
- [[Payment_Pipeline]]
- [[Data_Flow_and_Storage]]
