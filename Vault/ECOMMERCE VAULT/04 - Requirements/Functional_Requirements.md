# Functional Requirements

Status: PROPOSED / CONSOLIDATED
Last Updated: 2026-09-23
Tags: #requirements #specs #marketplace

## 1. Stakeholders & Role-Based Access Control (RBAC)
- **Buyer (Age 16+):** Browses products, manages personal cart/wishlist, places orders, reviews purchased apparel.
- **Seller:** Onboards with approval flow, manages product listings and variants, tracks stock, fulfills orders, views sales analytics, receives Stripe Connect payouts.
- **Platform Admin:** Oversees platform catalog categories, reviews seller applications, moderates content and accounts, monitors platform-wide analytics and disputes.

## 2. Authentication, Users & Compliance
- Multi-role user registration and login issuing Laravel Sanctum Bearer tokens (`buyer`, `seller`, `admin`).
- **Age Acknowledgment Compliance Gate:** Explicit age confirmation (16+ requirement) collected during signup.
- Protected profile management and multi-address book support (`addresses` table).

## 3. Product Catalog & Apparel Variants
- Hierarchical category tree with sub-category nesting (`categories` table with self-referencing `parent_id`).
- Product listing with filtering (size, color, brand, price range), sorting, and search with suggestions.
- **Apparel Variant System:** Individual stock tracking, SKU generation, and price overrides by size and color (`product_variants`).
- Multi-image management with sort order and touch-friendly gallery presentation (`product_images`).

## 4. Shopping Cart & Wishlist
- Persistent session-backed or user-authenticated cart (`carts`, `cart_items`) tracking variant-level items.
- Server-side stock reservation checks prior to checkout initiation.
- Wishlist / Save-for-Later list linked to user account.

## 5. Checkout & Payments (Stripe Connect)
- Multi-vendor checkout calculation aggregating items across multiple sellers.
- Stripe Connect payment session generation and automated transfer splitting ([[ADR-006_Stripe_Connect_for_Multi_Seller_Payouts]]).
- Ingestion of Stripe webhooks with HMAC signature verification and idempotency logging.

## 6. Orders, Fulfillment & Seller Lifecycle
- Order lifecycle management (`pending`, `paid`, `processing`, `shipped`, `delivered`, `cancelled`).
- Line item level seller denormalization (`order_items.seller_id`) for accurate payout and fulfillment tracking.
- Seller order queue with fulfillment status updates and shipping notifications.

## 7. Verified Reviews & Ratings
- Post-purchase customer reviews and 1–5 star ratings strictly tied to verified order line items (`order_item_id`).

## Related Links
- [[CORE_MEMORY]]
- [[Apparel_Marketplace_Requirements_and_Design_Plan]]
- [[Non_Functional_Requirements]]
- [[Payment_Pipeline]]
- [[Domain_Model_and_Entities]]
- [[ADR-006_Stripe_Connect_for_Multi_Seller_Payouts]]
