# CI/CD and Cloud Infrastructure

Status: VERIFIED
Last Updated: 2026-09-23
Tags: #architecture #ci-cd #devops #cloud

## 1. Automation Pipeline (GitHub Actions)
```mermaid
graph LR
    Push[Push to main] --> Lint[Lint: Pint & Larastan]
    Lint --> Test[Test: PHPUnit]
    Test --> Build[Docker Multi-Stage Build]
    Build --> GHCR[Push Image to GHCR]
    GHCR --> Deploy[Deploy to OCI Host]
```

## 2. Cloud Infrastructure Architecture
- **Host Provider:** Oracle Cloud Infrastructure (OCI) Always-Free Tier.
  - VM Spec: Ampere A1 (ARM64), 4 OCPU, 24 GB RAM, 200 GB NVMe storage.
  - Cost: $0.00/month permanently.
- **Edge Proxy & CDN:** Cloudflare Free Tier.
  - SSL/TLS termination, HTTP/2 & HTTP/3 support, automatic DDoS protection, edge caching of static media.

## 3. Related Links
- [[CORE_MEMORY]]
- [[ADR-004_Multi_Stage_Docker_Builds_for_Production]]
- [[ADR-005_Oracle_Cloud_Always_Free_Hosting]]
- [[Docker_Containerization]]
