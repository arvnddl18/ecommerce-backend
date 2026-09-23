# Apparel E-Commerce Marketplace — Design & Requirements Plan

Status: VERIFIED (Core Marketplace Engine) · ROADMAP (Social Login, Shipping Labels, Hosted Onboarding)
Last Updated: 2026-09-23
Author: Arvin
Tags: #requirements #specs #marketplace #design-plan #apparel #calibrated

---

## 1. Project Context

### 1.1 Problem Statement
Local and branded apparel sellers lack an accessible, professional platform to list, manage, and sell products online with the same polish and trust signals as major e-commerce platforms. Buyers, particularly younger demographics, expect fast, visually engaging, mobile-first shopping experiences.

### 1.2 Business Context
This platform operates as a **marketplace**, not a single-seller store — multiple independent sellers list products under one platform, similar in structure to Shopify's merchant model but centralized rather than per-seller-hosted.

### 1.3 Stakeholders

| Role | Description | Access Scope |
|---|---|---|
| **Buyer** | Browses, purchases, and reviews apparel (age 16+) | Own account, own orders, own cart/wishlist |
| **Seller** | Lists products, manages inventory, fulfills orders, receives payouts | Own listings, own orders, own payout settings |
| **Platform Admin** | Oversees sellers, resolves disputes, manages platform-wide settings | All platform data, moderation actions |

### 1.4 Target Market Profile
- Age 16 and above (requires age-gate acknowledgment at signup)
- Mobile-first, visually driven shopping behavior
- Values fast checkout, trust signals (reviews, ratings), and aesthetic presentation
- Price-sensitive but influenced by presentation and perceived exclusivity/scarcity

---

## 2. Functional Requirements

> [!NOTE]
> Requirements below are classified as **[VERIFIED IN CODE]** (backed by passing PHPUnit feature tests and live React components) or **[PLANNED / ROADMAP]** (future production enhancements).

### 2.1 Buyer-Facing Features
- **[VERIFIED IN CODE]** User registration & token authentication issuing Laravel Sanctum tokens (`POST /api/v1/auth/register`, `login`).
- **[PLANNED / ROADMAP]** Social login (OAuth / Google / Apple via Laravel Socialite).
- **[VERIFIED IN CODE]** Age acknowledgment at signup (16+ compliance gate checkbox).
- **[VERIFIED IN CODE]** Product browsing with category and filter navigation (size, color, seller store, price).
- **[VERIFIED IN CODE]** Product search with live autocomplete/suggestions (`GET /api/v1/products/suggestions`).
- **[VERIFIED IN CODE]** Product detail modal (two-zone layout, physical garment tag spec module, reviews, stock status).
- **[VERIFIED IN CODE]** Shopping cart with variant-level line items (`CartService.php`, Redis/cache-backed).
- **[VERIFIED IN CODE]** Promotional coupon & voucher redemption with real-time recalculation (`POST /api/v1/cart/coupon`).
- **[VERIFIED IN CODE]** Wishlist / save-for-later collection modal (`POST /api/v1/wishlist/{id}/toggle`).
- **[VERIFIED IN CODE]** Multi-address book management (`/api/v1/addresses`).
- **[VERIFIED IN CODE]** 3-Step Checkout flow (Bag → Delivery Address → Stripe Hosted Checkout).
- **[VERIFIED IN CODE]** Order tracking and customer purchase history modal (`GET /api/v1/orders`).
- **[VERIFIED IN CODE]** Product reviews and ratings strictly restricted to verified purchasers of the item.
- **[VERIFIED IN CODE]** Guest checkout via unique `X-Cart-Token` guest tokens.

### 2.2 Seller-Facing Features
- **[VERIFIED IN CODE]** Seller registration with approval status moderation (`verification_status`).
- **[VERIFIED IN CODE]** Seller dashboard overview (total revenue, items sold, active listings, low-stock variant alerts).
- **[PLANNED / ROADMAP]** Time-series sales analytics charts (historical daily/monthly revenue trends visualizer).
- **[VERIFIED IN CODE]** Product management (create, edit cuts, and archive listings in `SellerDashboard.tsx`).
- **[VERIFIED IN CODE]** Category and sub-category assignment for listings.
- **[VERIFIED IN CODE]** Variant management (size, color, stock per variant, price overrides).
- **[VERIFIED IN CODE]** Product image upload directly to storage (`POST /api/v1/seller/media/upload`).
- **[VERIFIED IN CODE]** Inventory tracking with low-stock warnings (`stock <= 5`).
- **[VERIFIED IN CODE]** Order management (view order items, update fulfillment status: `pending`, `processing`, `shipped`, `delivered`, `cancelled`).
- **[PLANNED / ROADMAP]** Physical shipping label printing (PDF/carrier generation).
- **[VERIFIED IN CODE]** Stripe Connect payout configuration and automated `\Stripe\Transfer::create` transfers.
- **[PLANNED / ROADMAP]** Live Stripe Connect hosted AccountLink KYC onboarding redirect.
- **[VERIFIED IN CODE]** Shop-scoped discount coupon creation (`POST /api/v1/seller/coupons`).

### 2.3 Admin-Facing Features
- **[VERIFIED IN CODE]** Seller application moderation queue (approve/reject onboarding in `AdminPanel.tsx`).
- **[VERIFIED IN CODE]** Hierarchical category management with parent/child tree (`POST/PUT/DELETE /api/v1/admin/categories`).
- **[VERIFIED IN CODE]** User account suspension moderation (`PUT /api/v1/admin/users/{user}/ban`).
- **[VERIFIED IN CODE]** Order refund pipeline with atomic stock restoration (`POST /api/v1/admin/orders/{order}/refund`).
- **[PLANNED / ROADMAP]** Customer dispute management board and content reporting pipeline.
- **[VERIFIED IN CODE]** Platform-wide analytics oversight (total GMV, order volume, buyer counts, recent order stream).

### 2.4 Platform-Wide Features
- **[VERIFIED IN CODE]** Multi-vendor payment processing via Stripe Checkout (`transfer_group = "ORDER_{number}"`).
- **[VERIFIED IN CODE]** Automated vendor payout disbursement (10% platform commission retained, 90% credited to maker).
- **[VERIFIED IN CODE]** Transactional email notifications via background queue (`OrderConfirmationMail`, `SellerOrderNotificationMail`).
- **[VERIFIED IN CODE]** "The Rail & The Rack" responsive storefront UI across mobile, tablet, and desktop.

---

## 3. Non-Functional Requirements

| Category | Requirement | Target Metric |
|---|---|---|
| **Performance** | Fast loading on mobile networks; checkout completes without noticeable lag | < 2s on 4G networks |
| **Scalability** | Stateless application layer; horizontally scalable | Container replication behind reverse proxy |
| **Availability** | Portfolio-grade uptime | 99.5%+ uptime |
| **Security** | All traffic over HTTPS; passwords hashed (bcrypt/argon2); PCI compliance delegated to Stripe | Zero raw cardholder data stored |
| **Usability** | Mobile-first responsive design; streamlined checkout | Checkout in ≤ 3 steps |
| **Accessibility** | Accessible colors and interaction patterns | WCAG 2.1 AA-aligned contrast, alt text, keyboard navigation |
| **Data Privacy** | Compliance-minded handling of user data; clear terms & privacy policy | 16+ minor protection compliance |
| **Maintainability** | Modular codebase, documented API, automated tests, CI/CD pipeline | 100% passing tests, automated linting |
| **SEO** | Server-rendered or pre-rendered product pages, clean URL slugs | Schema.org Product structured data |
| **Portability** | Fully containerized architecture | Deployable to any Docker-compatible host |

---

## 4. Architectural Design

### 4.1 High-Level Architecture

```
┌─────────────────────────────────────────────┐
│                  Cloudflare                  │
│         (CDN, SSL, DDoS protection)          │
└────────────────────┬──────────────────────────┘
                      │
              ┌───────▼────────┐
              │     Nginx       │
              │ (reverse proxy) │
              └───────┬────────┘
                      │
        ┌─────────────┼──────────────┐
        │                              │
┌───────▼────────┐          ┌─────────▼─────────┐
│  React Frontend │          │   Laravel API      │
│  (Buyer + Seller │◄────────►  (Business logic,  │
│   + Admin UIs)   │   REST   │   Auth, Orders)    │
└─────────────────┘          └─────────┬─────────┘
                                        │
                       ┌────────────────┼────────────────┐
                       │                │                 │
                ┌──────▼─────┐   ┌──────▼─────┐   ┌───────▼──────┐
                │ PostgreSQL  │   │   Redis     │   │ Stripe Connect│
                │ (persistent │   │ (cache,     │   │  (payouts,    │
                │  data)      │   │  sessions)  │   │   webhooks)   │
                └─────────────┘   └─────────────┘   └──────────────┘
```

### 4.2 Application Modules (Backend)

| Module | Responsibility |
|---|---|
| **Auth** | Registration, login, role-based access (buyer/seller/admin), Sanctum tokens |
| **Catalog** | Products, categories, variants, media |
| **Cart & Checkout** | Cart state, order creation, checkout session |
| **Orders** | Order lifecycle, fulfillment status, history |
| **Payments** | Stripe Connect integration, payouts to sellers, webhook handling |
| **Reviews** | Post-purchase reviews and ratings (verified purchase check) |
| **Seller Management** | Seller onboarding, verification, dashboard data |
| **Admin** | Moderation, platform analytics, dispute handling |
| **Notifications** | Order and account event notifications via background queue |

### 4.3 Frontend Structure
Three distinct interfaces sharing a common design system:
1. **Storefront:** Buyer-facing, public, SEO-critical — product browsing and checkout.
2. **Seller Dashboard:** Authenticated, seller-only — listing and fulfillment management.
3. **Admin Panel:** Authenticated, admin-only — platform oversight and dispute handling.

Implemented as route groups within a unified React application with role-based routing.

---

## 5. UI/UX Design Plan

### 5.1 Design Principles
- **Visual-first presentation:** Large, high-quality product imagery is the primary selling tool.
- **Mobile-first:** Target demographic browses predominantly on mobile.
- **Frictionless path to purchase:** Minimize clicks between discovery and checkout.
- **Trust signals throughout:** Ratings, review counts, seller verification badges visible at every stage.

### 5.2 Ethical Psychological Purchase Triggers
- **Scarcity indicators:** "Only 3 left in stock" (shown only when genuinely accurate).
- **Social proof:** Review counts, star ratings, "X sold this month".
- **Urgency for promotions:** Countdown timers on genuine time-limited discounts.
- **Personalization:** "Recommended for you" based on browsing/purchase history.
- **Frictionless add-to-cart:** Instant visual feedback (animation) reinforcing the action without leaving the page.

### 5.3 Motion & Interaction Stack ([[ADR-007_Framer_Motion_and_Swiper_for_Frontend_Experience]])
- **Framer Motion:** Primary general animation layer (product card hover scale/zoom, add-to-cart micro-interaction bounce/badge increment, page transitions, skeleton loading shimmer, animated checkout progress step indicator).
- **Swiper.js:** Dedicated touch-friendly gallery & carousel layer (swipeable product photo galleries, homepage trending carousels, related-product sliders, mobile pinch-to-zoom).

---

## 6. Database Schema Design

### 6.1 Core Entities Overview (Actual Relational Schema)
```
users ─────┬──── addresses
           ├──── seller_profiles (holds stripe_account_id)
           ├──── orders ──── order_items (has variant_id, seller_id)
           ├──── wishlists
           └──── reviews (linked to order_item_id)

categories ──── products ──── product_variants
                    │
                    └──── product_images

coupons (global or seller-scoped)
webhook_events (idempotency ledger)

Note: Cart state is held in Redis/Cache (CartService.php), not an SQL table.
Note: Payments (stripe_session_id, stripe_payment_intent_id) are columns on orders.
```

### 6.2 Key Tables Specification
- `users`: `id`, `name`, `email`, `password`, `role` (`buyer`, `seller`, `admin`), `age_verified` (boolean), `is_banned` (boolean), timestamps.
- `seller_profiles`: `id`, `user_id` (FK), `store_name`, `slug`, `verification_status` (`pending`, `approved`, `rejected`), `stripe_account_id`, `bio`, `rating`, `total_sales`.
- `categories`: `id`, `parent_id` (nullable self FK), `name`, `slug`, `description`.
- `products`: `id`, `seller_id` (FK), `category_id` (nullable FK), `name`, `slug`, `description`, `price` (cents), `stock`, `sku`, `status`, `is_active`, `images` (json).
- `product_variants`: `id`, `product_id` (FK), `size`, `color`, `sku` (unique), `stock_quantity`, `price_override` (nullable cents).
- `product_images`: `id`, `product_id` (FK), `url`, `sort_order`.
- `cart`: Managed in-memory/cache via `CartService.php` (`cart:{token}` and `cart:coupon:{token}`) tracking variants, quantities, and active discount codes.
- `orders`: `id`, `user_id` (nullable FK), `order_number`, `status` (`pending`, `paid`, `processing`, `completed`, `cancelled`, `refunded`), `total_amount`, `currency`, `stripe_session_id`, `stripe_payment_intent_id`, `customer_email`, `customer_name`, `shipping_address` (json), `billing_address` (json), `metadata` (json).
- `order_items`: `id`, `order_id` (FK), `product_id` (FK), `seller_id` (FK denormalized for vendor payout splitting), `variant_id` (nullable FK), `variant_details` (json), `product_name`, `unit_price`, `quantity`, `total_price`, `fulfillment_status`.
- `coupons`: `id`, `seller_id` (nullable FK), `code`, `discount_percent`, `discount_amount`, `min_order_amount`, `max_uses`, `uses_count`, `expires_at`, `is_active`.
- `webhook_events`: `id`, `stripe_event_id` (unique), `type`, `payload` (json), `processed_at`.
- `reviews`: `id`, `product_id` (FK), `user_id` (FK), `order_item_id` (nullable FK - ensures verified purchase), `rating` (1–5), `comment`, `status`.
- `wishlists`: `id`, `user_id` (FK), `product_id` (FK).
- `addresses`: `id`, `user_id` (FK), `line1`, `line2`, `city`, `province`, `postal_code`, `country`, `is_default`.

---

## 7. Multi-Seller Payments Architecture
- Marketplace model requires **Stripe Connect** ([[ADR-006_Stripe_Connect_for_Multi_Seller_Payouts]]) to split checkout payments between platform commission and individual vendor accounts.

---

## 8. Related Links
- [[CORE_MEMORY]]
- [[Functional_Requirements]]
- [[Non_Functional_Requirements]]
- [[Domain_Model_and_Entities]]
- [[System_Architecture]]
- [[Payment_Pipeline]]
- [[ADR-006_Stripe_Connect_for_Multi_Seller_Payouts]]
- [[ADR-007_Framer_Motion_and_Swiper_for_Frontend_Experience]]
- [[Developer_and_System_Preferences]]
