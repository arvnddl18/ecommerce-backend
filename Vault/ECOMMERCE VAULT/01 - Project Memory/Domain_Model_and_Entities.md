# Domain Model & Core Entities

Status: PROPOSED / CONSOLIDATED
Last Updated: 2026-09-23
Tags: #domain #models #database #schema #marketplace

## 1. Entity Relationship Overview
```
users ─────┬──── addresses
           ├──── seller_profiles ──── payout_accounts
           ├──── carts ──── cart_items
           ├──── orders ──── order_items
           ├──── wishlists
           └──── reviews

categories ──── products ──── product_variants
                    │
                    ├──── product_images
                    └──── reviews

orders ──── payments
orders ──── order_items ──── product_variants
```

## 2. Database Entities Specification

### 1. `users`
- Authenticated via Laravel Sanctum tokens.
- Fields: `id`, `name`, `email`, `password`, `role` (`buyer`, `seller`, `admin`), `age_verified` (boolean compliance gate for 16+), timestamps.

### 2. `seller_profiles`
- Store identity and approval status for independent vendors.
- Fields: `id`, `user_id` (FK → `users`), `store_name`, `verification_status` (`pending`, `approved`, `rejected`), `payout_method` (Stripe Connect account reference).

### 3. `categories`
- Hierarchical catalog taxonomy with nested subcategories.
- Fields: `id`, `parent_id` (nullable self FK → `categories`), `name`, `slug`.

### 4. `products`
- Top-level apparel listings owned by seller stores.
- Fields: `id`, `seller_id` (FK → `seller_profiles`), `category_id` (FK → `categories`), `name`, `slug`, `description`, `base_price` (integer cents), `status` (`draft`, `active`, `archived`).

### 5. `product_variants`
- Matrix inventory records holding specific size, color, SKU, and stock.
- Fields: `id`, `product_id` (FK → `products`), `size`, `color`, `sku` (unique), `stock_quantity`, `price_override` (nullable decimal/cents).

### 6. `product_images`
- Visual assets for apparel listings with ordering support.
- Fields: `id`, `product_id` (FK → `products`), `url`, `sort_order`.

### 7. `carts` & `cart_items`
- Persistent or guest session shopping cart holding variant selections.
- Fields: `carts` (`id`, `user_id` nullable), `cart_items` (`id`, `cart_id`, `variant_id`, `quantity`).

### 8. `orders` & `order_items`
- Multi-seller aggregate orders with denormalized seller references for payment splitting.
- Fields: `orders` (`id`, `user_id`, `status`: `pending`, `paid`, `shipped`, `delivered`, `cancelled`, `total_amount`, `shipping_address_id`).
- Fields: `order_items` (`id`, `order_id`, `variant_id`, `seller_id` denormalized for payout calculation, `quantity`, `unit_price`).

### 9. `payments`
- Stripe payment intent and multi-seller disbursement records.
- Fields: `id`, `order_id`, `stripe_payment_intent_id`, `status` (`pending`, `succeeded`, `failed`, `refunded`), `amount`.

### 10. `reviews`
- Verified post-purchase buyer feedback.
- Fields: `id`, `product_id`, `user_id`, `order_item_id` (FK ensuring verified purchase), `rating` (1–5), `comment`.

### 11. `wishlists`
- Save-for-later product bookmarks.
- Fields: `id`, `user_id`, `product_id`.

### 12. `addresses`
- Customer shipping and billing destinations.
- Fields: `id`, `user_id`, `line1`, `line2`, `city`, `province`, `postal_code`, `country`.

## 3. Related Links
- [[CORE_MEMORY]]
- [[Apparel_Marketplace_Requirements_and_Design_Plan]]
- [[Data_Flow_and_Storage]]
- [[ADR-002_PostgreSQL_16_over_MySQL]]
- [[ADR-006_Stripe_Connect_for_Multi_Seller_Payouts]]
