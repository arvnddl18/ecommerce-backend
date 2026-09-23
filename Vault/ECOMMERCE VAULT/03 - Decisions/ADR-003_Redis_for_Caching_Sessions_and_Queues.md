# ADR-003: Redis for Caching, Sessions, and Queues

Status: VERIFIED
Date: 2026-09-23
Tags: #adr #redis #architecture

## Context
High-traffic e-commerce operations require sub-millisecond response times for session state checks, cached product lists, and reliable asynchronous processing for order fulfillment and webhook handling.

## Decision
Deploy a dedicated Redis 7 container serving as the unified in-memory store for cache, session storage, and queue job dispatching.

## Rationale & Consequences
- **Unified Simplicity:** One lightweight service covers 3 crucial backend concerns without needing separate daemons (like RabbitMQ or Memcached).
- **Zero Disk I/O Bottlenecks:** Sub-millisecond reads for cached catalog queries and rate-limiting counters.
- **Reliable Queuing:** Provides atomic queue operations for the `worker` container to process background tasks.
- **Trade-off:** In-memory volatility mitigated by enabling AOF/RDB persistence on a named Docker volume.

## Related Links
- [[CORE_MEMORY]]
- [[Data_Flow_and_Storage]]
- [[Redis_Queues_and_Worker_Architecture]]
