# ADR-006: Stripe Connect for Multi-Seller Payouts

Status: VERIFIED (Payout Splitting, AccountLink Onboarding & Stripe Sync)
Date: 2026-09-23
Tags: #adr #architecture #payments #stripe #marketplace #calibrated

## Context
The platform is transitioning from a single-merchant storefront to a multi-vendor marketplace where independent apparel sellers list products, manage orders, and receive payouts under a centralized platform umbrella. A standard single-merchant Stripe Checkout integration cannot automatically disburse seller payouts, hold reserves, or split transaction funds across multiple vendor bank accounts while deducting platform commissions.

## Decision
Adopt **Stripe Connect** (utilizing Express or Custom connected accounts) to handle seller onboarding, automated payment splitting, and multi-vendor disbursements.

## Rationale & Consequences
- **Automated Payout Splitting:** Orders containing items from multiple sellers (`order_items.seller_id`) can split charges dynamically (separate transfers or transfer groups) while collecting the platform's application fee.
- **Compliance & KYC Offloading:** Stripe Connect handles KYC, tax form generation (1099-NEC/K), and international banking verification for registered sellers.
- **Webhook & Lifecycle Handling:** Requires listening to connected account webhooks (`account.updated`, `transfer.created`, `payout.paid`) in addition to core payment intent webhooks.
- **Data Model Impact:** Introduces `seller_profiles` and payout account IDs to track verified seller connections.

## Verification in Codebase
- **Transfer Splitting Execution:** Fully implemented via `\Stripe\Transfer::create()` in [ProcessStripeWebhookJob.php](file:///c:/arvincodework/ecommerce-backend/app/Jobs/ProcessStripeWebhookJob.php).
- **Commission Allocation:** 10% platform commission deducted automatically; 90% net revenue credited to `seller_profiles.stripe_account_id` with `transfer_group = "ORDER_{number}"`.
- **Transactional Notifications:** Queues [OrderConfirmationMail.php](file:///c:/arvincodework/ecommerce-backend/app/Mail/OrderConfirmationMail.php) to buyer and [SellerOrderNotificationMail.php](file:///c:/arvincodework/ecommerce-backend/app/Mail/SellerOrderNotificationMail.php) to makers.
- **Automated QA:** Covered by [StripeConnectTransferAndEmailNotificationTest.php](file:///c:/arvincodework/ecommerce-backend/tests/Feature/StripeConnectTransferAndEmailNotificationTest.php) and [StripeSyncOrdersCommandTest.php](file:///c:/arvincodework/ecommerce-backend/tests/Feature/StripeSyncOrdersCommandTest.php).
- **Merchant Onboarding Status:** Implemented in [SellerController.php::payoutSetup](file:///c:/arvincodework/ecommerce-backend/app/Http/Controllers/Api/v1/SellerController.php#L293-L350) and [StripeService.php](file:///c:/arvincodework/ecommerce-backend/app/Services/StripeService.php) with live `\Stripe\AccountLink::create()` support when Connect is active, and resilient fallback for local simulations.
- **Order Payment Synchronization:** Implemented in [StripeSyncOrdersCommand.php](file:///c:/arvincodework/ecommerce-backend/app/Console/Commands/StripeSyncOrdersCommand.php) (`php artisan stripe:sync-orders`) to query Stripe Checkout directly and fulfill paid orders even when local webhooks are delayed.

## Related Links
- [[CORE_MEMORY]]
- [[Payment_Pipeline]]
- [[Apparel_Marketplace_Requirements_and_Design_Plan]]
- [[Domain_Model_and_Entities]]
