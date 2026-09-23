# System Architecture

Status: PROPOSED / CONSOLIDATED
Last Updated: 2026-09-23
Tags: #architecture #system-design #marketplace

## 1. High-Level Topology

```mermaid
graph TD
    subgraph Clients["React 18 Frontend Surfaces"]
        BuyerUI["Storefront (Buyer 16+)"]
        SellerUI["Seller Dashboard"]
        AdminUI["Admin Panel"]
    end

    Clients -->|HTTPS :443| Cloudflare[Cloudflare CDN & Edge SSL]
    Cloudflare -->|Port 80/443| Nginx[Webserver: Nginx Alpine]
    Nginx -->|FastCGI :9000| App[App: Laravel 11 PHP-FPM]
    Worker[Worker: php artisan queue:work] -->|Redis Queues| Redis[(Redis 7)]
    Scheduler[Scheduler: php artisan schedule:work] -->|Cron Runs| App
    App -->|SQL :5432| DB[(PostgreSQL 16)]
    App -->|Cache & Queues| Redis
    App -->|REST API| Stripe[Stripe Connect & Checkout]
    Stripe -->|Webhooks :443| Nginx
```

## 2. Layer Responsibilities
- **Edge Layer (Cloudflare):** Terminating SSL, caching static assets, rate limiting, and DDoS filtering.
- **Web Proxy Layer (Nginx):** Reverse proxy, serving gzip-compressed assets, routing API traffic to PHP-FPM, static file caching.
- **Client Presentation Layer (React 18):** Three unified route surfaces (Storefront, Seller Dashboard, Admin Panel) implementing "The Rail & The Rack" bespoke design system ([[Frontend_Design_System_Architecture]]) powered by Framer Motion and Swiper.js ([[ADR-007_Framer_Motion_and_Swiper_for_Frontend_Experience]], [[ADR-008_The_Rail_and_The_Rack_Storefront_Design_System]]).
- **Application Layer (Laravel 11):** Stateless JSON REST API handling RBAC auth (buyer/seller/admin), catalog and variants, cart, order orchestration, and Stripe Connect.
- **Worker Layer (PHP-FPM CLI):** Background queue consumer executing webhook verification, payout splits, and notification dispatches via Redis.
- **Persistent Data Layer (PostgreSQL 16):** ACID transactional storage managing users, seller profiles, product variants, orders, and payments.
- **In-Memory Layer (Redis 7):** Key-value caching for catalogs, user cart states, and asynchronous queue broker.

## 3. Related Links
- [[CORE_MEMORY]]
- [[Apparel_Marketplace_Requirements_and_Design_Plan]]
- [[Frontend_Design_System_Architecture]]
- [[Docker_Containerization]]
- [[Payment_Pipeline]]
- [[Data_Flow_and_Storage]]
- [[ADR-006_Stripe_Connect_for_Multi_Seller_Payouts]]
- [[ADR-007_Framer_Motion_and_Swiper_for_Frontend_Experience]]
- [[ADR-008_The_Rail_and_The_Rack_Storefront_Design_System]]
