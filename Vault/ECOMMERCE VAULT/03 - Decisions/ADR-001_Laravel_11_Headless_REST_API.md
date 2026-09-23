# ADR-001: Laravel 11 as Headless REST API

Status: VERIFIED
Date: 2026-09-23
Tags: #adr #architecture #api

## Context
We need a robust, scalable backend architecture for an e-commerce platform that supports modern frontend client interfaces (React 18 / TypeScript) and potential future mobile applications without coupling presentation logic to server rendering.

## Decision
Adopt Laravel 11 configured strictly as a headless JSON REST API, utilizing Laravel Sanctum for API token issuance and validation.

## Rationale & Consequences
- **Decoupled Development:** Frontend and backend can evolve, be tested, and deployed independently.
- **Client Agnostic:** The same JSON API serves web, mobile, and third-party integrations.
- **Stateless HTTP:** Enables trivial horizontal scaling of PHP-FPM containers behind load balancers.
- **Trade-off:** Requires configuring CORS policies and handling SEO via client-side pre-rendering or SSR for public catalog pages.

## Related Links
- [[CORE_MEMORY]]
- [[System_Architecture]]
