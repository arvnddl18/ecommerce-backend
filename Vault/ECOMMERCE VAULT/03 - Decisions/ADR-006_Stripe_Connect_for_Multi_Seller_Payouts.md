# ADR-006: Stripe Connect for Multi-Seller Payouts

Status: PROPOSED
Date: 2026-09-23
Tags: #adr #architecture #payments #stripe #marketplace

## Context
The platform is transitioning from a single-merchant storefront to a multi-vendor marketplace where independent apparel sellers list products, manage orders, and receive payouts under a centralized platform umbrella. A standard single-merchant Stripe Checkout integration cannot automatically disburse seller payouts, hold reserves, or split transaction funds across multiple vendor bank accounts while deducting platform commissions.

## Decision
Adopt **Stripe Connect** (utilizing Express or Custom connected accounts) to handle seller onboarding, automated payment splitting, and multi-vendor disbursements.

## Rationale & Consequences
- **Automated Payout Splitting:** Orders containing items from multiple sellers (`order_items.seller_id`) can split charges dynamically (separate transfers or transfer groups) while collecting the platform's application fee.
- **Compliance & KYC Offloading:** Stripe Connect handles KYC, tax form generation (1099-NEC/K), and international banking verification for registered sellers.
- **Webhook & Lifecycle Handling:** Requires listening to connected account webhooks (`account.updated`, `transfer.created`, `payout.paid`) in addition to core payment intent webhooks.
- **Data Model Impact:** Introduces `seller_profiles` and payout account IDs to track verified seller connections.

## Related Links
- [[CORE_MEMORY]]
- [[Payment_Pipeline]]
- [[Apparel_Marketplace_Requirements_and_Design_Plan]]
- [[Domain_Model_and_Entities]]
