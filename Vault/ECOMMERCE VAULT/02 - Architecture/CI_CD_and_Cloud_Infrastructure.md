# CI/CD and Cloud Infrastructure

Status: VERIFIED (CI Workflow) / PROPOSED (Production Cloud Deploy)
Last Updated: 2026-09-23
Tags: #architecture #ci-cd #devops #cloud #verified #proposed

> [!NOTE]
> The GitHub Actions automation (`.github/workflows/ci.yml`) is **VERIFIED** for automated Lint (Pint, Larastan, TypeScript), Test (PHPUnit with Postgres/Redis service containers), and Docker Build/Push to GHCR.
> The downstream deployment to an OCI host and Cloudflare CDN configuration are **PROPOSED** production targets and not yet live.

## 1. Automation Pipeline (GitHub Actions)
```mermaid
graph LR
    Push[Push to main] --> Lint[Lint: Pint & Larastan (VERIFIED)]
    Lint --> Test[Test: PHPUnit & Postgres (VERIFIED)]
    Test --> Build[Docker Build & Push to GHCR (VERIFIED)]
    Build -.-> Deploy[Deploy to OCI Host (PROPOSED)]
```

## 2. Cloud Infrastructure Architecture (Proposed Target)
- **Host Provider:** Oracle Cloud Infrastructure (OCI) Always-Free Tier (Target Blueprint).
  - VM Spec: Ampere A1 (ARM64), 4 OCPU, 24 GB RAM, 200 GB NVMe storage.
  - Cost: $0.00/month permanently.
- **Edge Proxy & CDN:** Cloudflare Free Tier (Planned).
  - SSL/TLS termination, HTTP/2 & HTTP/3 support, automatic DDoS protection, edge caching of static media.

## 3. Related Links
- [[CORE_MEMORY]]
- [[ADR-004_Multi_Stage_Docker_Builds_for_Production]]
- [[ADR-005_Oracle_Cloud_Always_Free_Hosting]]
- [[Docker_Containerization]]
