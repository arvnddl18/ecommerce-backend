# Non-Functional Requirements

Status: VERIFIED
Last Updated: 2026-09-23
Tags: #requirements #specs #security #performance

## 1. Performance & Latency
- Sub-50ms API response time for cached catalog and session verification requests.
- Sub-5s webhook response time to Stripe endpoints to avoid timeout retries.

## 2. Security & Compliance
- **Zero Cardholder Data Storage:** Never store raw credit card numbers, CVVs, or expiration dates on local infrastructure.
- **Webhook Signature Validation:** Reject all unverified webhook payloads.
- **Token-based Authentication:** Expiring Sanctum API tokens with scoped abilities.
- **Environment Isolation:** Secrets and credentials strictly in `.env`, excluded from Git.

## 3. Reliability & Portability
- **Environment Parity:** Docker Compose ensures identical execution across local dev and production.
- **Data Persistence:** Dedicated named Docker volumes for PostgreSQL and Redis.

## 4. Cost Efficiency
- Total cloud hosting footprint engineered to run perpetually within $0.00/month free-tier quotas (OCI Always-Free + Cloudflare).

## Related Links
- [[CORE_MEMORY]]
- [[Functional_Requirements]]
- [[System_Architecture]]
