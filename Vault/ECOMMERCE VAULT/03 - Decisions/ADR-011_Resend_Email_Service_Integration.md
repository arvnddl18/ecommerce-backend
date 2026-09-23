# ADR-011: Resend Transactional Email Service Integration

Status: VERIFIED
Date: 2026-09-24
Deciders: Arvin & Core Team
Tags: #adr #architecture #email #resend #security #notifications #verified

---

## Context & Problem Statement
The marketplace requires transactional email notifications for core business events:
1. Buyer order confirmation receipts when Stripe checkout sessions succeed.
2. Seller fulfillment dispatch alerts when customers order garments from their boutique studio.
3. Future lifecycle notifications (order shipping/tracking updates, administrative refunds, seller application approvals, and security alerts).

Historically, the application defaulted to the `log` mail driver during initial development. To transition toward production readiness and high-deliverability email infrastructure, the platform needed a modern, developer-first transactional email provider.

## Decision
We integrate **[Resend](https://resend.com/)** as the official email service provider for the platform:

1. **Native Laravel Transport Driver:**
   - Utilize Laravel's native Resend mail transport (`Illuminate\Mail\Transport\ResendTransport`) enabled via `resend/resend-php:^1.15`.
   - Configure `MAIL_MAILER=resend` in the environment.
   - Map `services.resend.key` to `env('RESEND_API_KEY')` in `config/services.php`.

2. **Secrets & Credentials Security Guardrail:**
   - The live API key is strictly maintained in the untracked `.env` file (`RESEND_API_KEY=re_...`).
   - `.gitignore` enforces wildcard ignore patterns for `.env` and `.env.*` (safeguarding all current and future variant files while whitelisting `.env.example`, `.env.production.example`, and `.env.docker`).
   - `.dockerignore` blocks `.env` and `.env.*` from being injected or cached in container images.
   - Example templates (`.env.example`, `.env.docker`, `.env.production.example`) retain safe placeholder values (`RESEND_API_KEY=re_your_api_key_here`) to comply with GitHub Push Protection and prevent Git history credential leaks.
   - Raw API key strings are **strictly forbidden** from being stored in Obsidian Vault markdown notes.

3. **Domain Verification & Sandbox Constraints:**
   - In development/sandbox environments prior to custom domain verification, emails are dispatched using `MAIL_FROM_ADDRESS="onboarding@resend.dev"`.
   - For production, custom domains (e.g. `mail.yourdomain.com`) must be verified via SPF, DKIM, and DMARC DNS records in the Resend dashboard to deliver to arbitrary external recipient domains.

## Consequences

### Positive
- **High Deliverability & Performance:** Modern HTTP API-based email delivery with sub-second transmission times compared to traditional SMTP overhead.
- **Native Framework Integration:** Built-in Laravel `Mail::queue()` support works seamlessly without custom wrapper boilerplate.
- **Multi-Vendor Architecture Support:** Decouples buyer receipt dispatch and seller studio dispatch notifications cleanly inside asynchronous queue workers (`ProcessStripeWebhookJob`).
- **Zero Secret Exposure:** Absolute separation of live keys from Git and Docker build contexts.

### Negative / Trade-offs
- Unverified sandbox domains (`resend.dev`) are restricted to sending emails only to the verified account owner email during initial testing. Custom domain verification in the Resend dashboard is mandatory for open customer receipt delivery.

## Related Links
- [[CORE_MEMORY]]
- [[Payment_Pipeline]]
- [[Resend_Email_Infrastructure_and_Lifecycle]]
- [[GitHub_Push_Protection_and_Environment_Secrets_Leak_Remediation]]
- [[ADR-006_Stripe_Connect_for_Multi_Seller_Payouts]]
