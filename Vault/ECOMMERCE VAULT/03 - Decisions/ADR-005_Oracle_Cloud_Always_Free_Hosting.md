# ADR-005: Oracle Cloud Always-Free Hosting

Status: VERIFIED
Date: 2026-09-23
Tags: #adr #cloud #hosting

## Context
Running a complete multi-container Docker stack (Nginx, PHP-FPM, Postgres, Redis, Queue Worker, Scheduler) typically requires a VPS costing $20–$50/month, whereas standard free-tier PaaS (Render, Railway, Fly.io) impose severe CPU/RAM limits, sleep cycles, and ephemeral databases.

## Decision
Host the production stack on Oracle Cloud Infrastructure (OCI) Ampere A1 (ARM64) Always-Free compute instance, reverse-proxied behind Cloudflare.

## Rationale & Consequences
- **True Production Specs for $0:** 4 ARM OCPUs, 24 GB RAM, 200 GB persistent NVMe storage, and 10 TB/month egress bandwidth for permanent $0.00/month.
- **Full Docker Freedom:** Runs native `docker compose` with persistent volumes and background worker daemons without container sleep penalties.
- **Global Speed:** Cloudflare edge terminates SSL and caches static assets globally.

## Related Links
- [[CORE_MEMORY]]
- [[CI_CD_and_Cloud_Infrastructure]]
