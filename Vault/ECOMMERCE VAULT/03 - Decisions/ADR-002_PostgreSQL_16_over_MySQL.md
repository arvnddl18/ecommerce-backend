# ADR-002: PostgreSQL 16 over MySQL

Status: VERIFIED (Docker / CI Target) · SQLite (Local Runtime)
Date: 2026-09-23
Tags: #adr #database #postgresql #sqlite #verified

> [!NOTE]
> PostgreSQL 16 is verified as the service container target in `docker-compose.yml` and `.github/workflows/ci.yml`. Local development currently executes against SQLite (`database/database.sqlite`) for rapid local iteration.

## Context
E-commerce data models require rigid ACID transaction guarantees, strong concurrency controls (row-level locking during inventory deduction), and native JSON capabilities for storing raw third-party webhook payloads (e.g. Stripe events).

## Decision
Select PostgreSQL 16 Alpine as the primary relational database instead of MySQL 8.

## Rationale & Consequences
- **Advanced JSONB:** Fast indexing and querying of unstructured event objects without schema changes.
- **Strict Concurrency:** Robust `SELECT ... FOR UPDATE` row locks eliminate checkout race conditions.
- **Industry Standard:** Matches production cloud systems and serverless Postgres options (Supabase, Neon).
- **Trade-off:** Slightly higher baseline memory footprint, readily accommodated by 24 GB RAM on Oracle Free Tier.

## Related Links
- [[CORE_MEMORY]]
- [[Data_Flow_and_Storage]]
- [[PostgreSQL_JSONB_and_Indexing]]
