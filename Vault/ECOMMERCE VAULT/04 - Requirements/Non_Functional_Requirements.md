# Non-Functional Requirements

Status: VERIFIED (Security & Accessibility Standards) · OPERATIONAL TARGETS (Latency & Uptime SLA)
Last Updated: 2026-09-23
Tags: #requirements #specs #security #performance #accessibility #calibrated

## 1. Performance & Latency (Target SLAs)
- Product pages and category listings load under 2 seconds on mobile 4G networks (Target SLA).
- Checkout flow completes smoothly without noticeable lag.
- Sub-50ms API response time for cached catalog and session verification requests under Redis caching (Target SLA).
- Sub-5s webhook response time to Stripe endpoints to prevent retry storms (Verified: webhook controller returns immediate 200 OK after dispatching job).

## 2. Scalability & Availability (Target Architecture)
- Stateless application layer horizontally scalable via container replication behind Nginx reverse proxy (Docker Production Spec).
- Target 99.5%+ uptime for production deployment (Operational Objective).

## 3. Security, Privacy & Compliance
- **Zero Raw Cardholder Data:** Server never touches raw card numbers; full PCI compliance delegated to Stripe Elements / Stripe Connect.
- **Data Privacy & Minor Protection:** Clear Terms of Service and Privacy Policy handling user data responsibly (critical for 16+ demographic).
- **HMAC Signature Validation:** Rejection of unverified Stripe webhook payloads.
- **Password Security:** Hashes generated strictly with Argon2id / Bcrypt.

## 4. Usability & Accessibility
- Mobile-first responsive design across phones, tablets, and desktops.
- Checkout completable in 3 steps or fewer to minimize drop-off.
- WCAG 2.1 AA-aligned color contrast, semantic HTML, alt text on product imagery, and full keyboard navigation support.

## 5. SEO & Portability
- Structured data markup (Schema.org `Product` JSON-LD) and clean SEO URL slugs.
- Fully containerized multi-stage Docker builds deployable to any Docker-compatible host.

## Related Links
- [[CORE_MEMORY]]
- [[Apparel_Marketplace_Requirements_and_Design_Plan]]
- [[Functional_Requirements]]
- [[System_Architecture]]
- [[Developer_and_System_Preferences]]
