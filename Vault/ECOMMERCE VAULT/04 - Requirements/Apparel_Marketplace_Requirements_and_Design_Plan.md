# Apparel E-Commerce Marketplace — Design & Requirements Plan

Status: PROPOSED
Last Updated: 2026-09-23
Author: Arvin
Tags: #requirements #specs #marketplace #design-plan #apparel

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

### 2.1 Buyer-Facing Features
- User registration/login (email, and optionally social login)
- Age acknowledgment at signup (13+/16+ compliance gate)
- Product browsing with category and filter navigation (size, color, price, brand)
- Product search with autocomplete/suggestions
- Product detail page (images, variants, sizing info, reviews, stock status)
- Shopping cart (add/remove/update quantity, persists across sessions)
- Wishlist / save-for-later
- Checkout flow (address, shipping method, payment)
- Order tracking and order history
- Product reviews and ratings (post-purchase only, tied to verified order item)
- Guest checkout (optional, reduces signup friction)

### 2.2 Seller-Facing Features
- Seller registration with verification/approval flow
- Seller dashboard (sales overview, order queue, revenue summary)
- Product management (create, edit, delete listings)
- Category and sub-category assignment for listings
- Variant management (size, color, stock per variant, price overrides)
- Product image/media upload with reordering
- Inventory tracking with low-stock alerts
- Order management (view, update fulfillment status, print shipping labels)
- Payment/payout setup (linking bank account or payment method via [[ADR-006_Stripe_Connect_for_Multi_Seller_Payouts]])
- Discount/coupon creation for own listings
- Sales analytics (top products, revenue trends)

### 2.3 Admin-Facing Features
- Seller application review and approval/rejection
- Platform-wide category management (hierarchical tree)
- User and seller account moderation (suspend/ban)
- Dispute and refund oversight
- Platform-wide analytics (GMV, active sellers, active buyers)
- Content moderation (flagged listings/reviews)

### 2.4 Platform-Wide Features
- Secure multi-seller payment processing via Stripe Connect
- Notification system (order updates, seller alerts — email at minimum via Redis queue)
- Search indexing across all seller listings
- Responsive design across mobile, tablet, desktop

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

### 6.1 Core Entities Overview
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

### 6.2 Key Tables Specification
- `users`: `id`, `name`, `email`, `password`, `role` (buyer, seller, admin), `age_verified` (boolean), timestamps.
- `seller_profiles`: `id`, `user_id` (FK), `store_name`, `verification_status` (pending, approved, rejected), `payout_method`.
- `categories`: `id`, `parent_id` (nullable self FK), `name`, `slug`.
- `products`: `id`, `seller_id` (FK), `category_id` (FK), `name`, `slug`, `description`, `base_price`, `status` (draft, active, archived).
- `product_variants`: `id`, `product_id` (FK), `size`, `color`, `sku` (unique), `stock_quantity`, `price_override`.
- `product_images`: `id`, `product_id` (FK), `url`, `sort_order`.
- `carts` / `cart_items`: `cart_id`, `variant_id` (FK), `quantity`.
- `orders`: `id`, `user_id` (FK), `status` (pending, paid, shipped, delivered, cancelled), `total_amount`, `shipping_address_id` (FK).
- `order_items`: `id`, `order_id` (FK), `variant_id` (FK), `seller_id` (FK denormalized for payout splitting), `quantity`, `unit_price`.
- `payments`: `id`, `order_id` (FK), `stripe_payment_intent_id`, `status`, `amount`.
- `reviews`: `id`, `product_id` (FK), `user_id` (FK), `order_item_id` (FK - ensures verified purchase), `rating`, `comment`.
- `wishlists`: `id`, `user_id` (FK), `product_id` (FK).
- `addresses`: `id`, `user_id` (FK), `line1`, `line2`, `city`, `province`, `postal_code`, `country`.

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
