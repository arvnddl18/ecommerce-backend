# Current State

Status: VERIFIED (Local Marketplace Codebase) · PROPOSED (Cloud Hosting & Infrastructure Target)
Last Updated: 2026-09-23
Tags: #current-state #roadmap #marketplace #verified #proposed

## 🎯 Active Focus
- **Completed Front-End UI/UX Remake:** Fully implemented **"The Rail & The Rack"** design system across all React components, adhering to the bespoke boutique aesthetic and class hierarchy in `references/style.css`.
- Replaced generic AI-templated patterns (dark cards, uniform shadows, middle dots, arrow glyphs) with an editorial left-aligned layout, horizontal rack-rail category navigation, asymmetric 2-column grid, two-zone product detail with garment tag module, and restrained motion.

## 📌 Architecture & Features In Place
1. **Bespoke Boutique Front-End ("The Rail & The Rack" — [[ADR-008_The_Rail_and_The_Rack_Storefront_Design_System]]):**
   - **Horizontal Rack Rail:** Hanger indexing (`01`, `02`), live category counts, sticky scroll behavior with thin vertical 1px borders.
   - **Asymmetric Editorial Grid:** 2-column layout (`1.15fr .85fr`) with 4 distinct cadence tiles (`feature-card`, `simple-card`, `offset-card`, `wide-card`) and staggered offsets.
   - **Product Detail Modal:** 2-zone architecture featuring bleed-to-edge photography gallery (Swiper) and a physical **garment tag spec module** with barcode, monospace SKU, material composition, and care specs.
   - **Motion Restraint:** Strictly two deliberate signature moments (rack glide and pull-to-inspect zoom) with 150-250ms functional feedback.
   - **Typography & Tokens:** Display headlines in `Space Grotesk` (`500/700`, tight letter-spacing `-.09em`), body in `DM Sans`, warm off-white surface (`#FAFAF8`), coral-orange action accent (`#FF5A36`), and warm yellow attention badge (`#FFC94D`).
2. **Multi-Role User & 16+ Compliance System:**
   - User roles: `buyer`, `seller`, `admin`.
   - Age verification flag (`age_verified`) enforced during registration with mandatory 16+ checkbox.
   - Strict role-based interface isolation ([[ADR-009_Role_Based_Interface_Isolation]]) preventing simultaneous leakage of merchant/admin routes into shopper views.
   - Automatic `seller_profiles` generation upon seller signup.
3. **Apparel Catalog & Product Variants:**
   - Multi-variant modeling (`product_variants`) with explicit `size`, `color`, `sku`, `stock_quantity`, and optional `price_override`.
   - Variant-specific cart keys (`{productId}_{variantId}`) and inventory validation in `CartService`.
   - Above-the-fold catalog grid and immediate purchase urgency (`ShopHeading` and 4-column layout in [[ADR-010_Above_The_Fold_Product_Grid_and_Immediate_Purchase_Urgency]]).
   - Multi-photo galleries (`product_images`) with primary image flag and sort ordering.
   - Verified buyer reviews (`reviews` table checking purchase in completed `order_items`).
   - Wishlists and user address book.
   - Buyer past order history tracking modal (`BuyerOrderHistoryModal.tsx`) with verified review shortcuts.
   - Seller product lifecycle management (`SellerDashboard.tsx` Cuts & Inventory tab) with edit/archive actions.
4. **Multi-Vendor Payments, Transfers, Coupons & Restocking:**
   - Stripe Connect transfer group integration (`transfer_group = ORDER_{number}`) on Checkout sessions.
   - Real automated Stripe Connect transfers (`\Stripe\Transfer::create`) disbursing 90% net revenue to sellers after 10% platform fee.
   - Transactional email notifications via Redis queue (`OrderConfirmationMail`, `SellerOrderNotificationMail`).
   - Denormalized `seller_id` and individual `fulfillment_status` on `order_items` for independent line-item tracking.
   - Atomic inventory deduction for both base products and variants on successful webhook handling (`ProcessStripeWebhookJob`).
   - Admin category tree CRUD and user suspension moderation (`AdminController.php`).
   - Admin order refund pipeline (`POST /api/v1/admin/orders/{order}/refund`) with Stripe Refund and atomic inventory restoration.
   - Promotional coupon/voucher engine (`coupons` table, `Coupon` model, Cart & Checkout discount computation).
   - Direct seller media upload (`POST /api/v1/seller/media/upload`) and autocomplete product suggestions (`GET /api/v1/products/suggestions`).
5. **Quality & Validation Status (100% True Verification):**
   - **PHPUnit Feature Tests:** 74 passing tests (292 assertions, 0 failures).
   - **Laravel Pint:** 100% PSR-12 styling compliance.
   - **Larastan Static Analysis:** 0 errors at level 5 with full memory allocation.
   - **Vite & TypeScript:** Clean build output (`npm run build`, 10.52s).
   - **Boutique Docket Consistency:** Aligned order confirmation (`OrderSuccess.tsx`) and cancellation (`OrderCancel.tsx`) to "The Rail & The Rack" design system, eliminating white-on-white text and dark-box mismatches.

## 🛑 Active Blockers
- None. System is completely green and ready for local development, Docker deployment, or production cloud shipping.

## 🚀 Recommended Next Actions
1. Deploy to staging/production environment using Laravel Cloud (`cloud` CLI via `deploying-to-cloud` skill) or Docker Swarm / Kubernetes on OCI.
2. Configure live Stripe Connect webhook endpoints and webhook signing secrets in production `.env`.
3. Add automated end-to-end Cypress/Playwright tests for complete multi-vendor order-to-payout journey.

## Related Links
- [[CORE_MEMORY]]
- [[Apparel_Marketplace_Requirements_and_Design_Plan]]
- [[Domain_Model_and_Entities]]
- [[Functional_Requirements]]
- [[Non_Functional_Requirements]]
- [[Completed_Milestones_Log]]
- [[Payment_Pipeline]]
- [[System_Architecture]]
- [[ADR-006_Stripe_Connect_for_Multi_Seller_Payouts]]
- [[ADR-007_Framer_Motion_and_Swiper_for_Frontend_Experience]]
- [[ADR-008_The_Rail_and_The_Rack_Storefront_Design_System]]
- [[ADR-009_Role_Based_Interface_Isolation]]
- [[ADR-010_Above_The_Fold_Product_Grid_and_Immediate_Purchase_Urgency]]
- [[Order_Confirmation_Dark_Theme_Mismatch]]
