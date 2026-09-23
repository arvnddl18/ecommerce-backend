# Developer and System Preferences

Status: VERIFIED
Last Updated: 2026-09-23
Tags: #preferences #conventions #standards

## 1. Coding & Code Style Standards
- **Strict Formatting:** Run `vendor/bin/pint --dirty --format agent` after modifying PHP code to ensure PSR-12 and Laravel style compliance.
- **Type Safety & Returns:** Use explicit PHP 8+ return type declarations, constructor property promotion, and PHPDoc blocks for array shapes.
- **Enums:** Use TitleCase for Enum keys (e.g. `OrderStatus::Pending`).
- **No Tinker Scripts as Tests:** Write dedicated PHPUnit feature tests (`tests/Feature/`) with model factories rather than throwaway scratch scripts.

## 2. Architecture & Tooling Preferences
- **Laravel Boost Tools:** Use Boost MCP tools (`database-query`, `database-schema`, `get-absolute-url`, `browser-logs`) when inspecting and developing Laravel features.
- **Container First:** Execute Artisan commands and tests via Docker Compose:
  - `docker compose exec app php artisan ...`
  - `docker compose exec app vendor/bin/phpunit`
- **Secrets Management:** Never commit `.env` or production credentials. Store secrets in environment variables or GitHub Actions Secrets.

## 3. UI/UX Design Principles & Motion Standards
- **Above-The-Fold Product Visibility & Immediate Purchasing Urgency ([[ADR-010_Above_The_Fold_Product_Grid_and_Immediate_Purchase_Urgency]]):**
  - **No Oversized Hero Banners:** Never hide products and prices beneath tall decorative hero banners or lengthy corporate prose. The first screen must immediately showcase physical merchandise, prices, star ratings, and category rails.
  - **Compact Shop Heading:** Use a compact, low-profile header (`.shop-heading`, eyebrow, headline, sort dropdown) with a footprint under 150px.
  - **4-Column Product Grid:** Render items in a 4-column arrangement (`repeat(4, 1fr)`) with hover slide-up `＋ Quick add` buttons and clear scarcity badges (`Low stock`, `New`).
  - **Story Follows Product:** Conceptual and brand storytelling (`.story-section`) must sit *below* the catalog grid, ensuring the customer sees what they can buy before learning about brand ethos.
- **Mobile-First Orientation:** Tailor layouts and hit targets for mobile shoppers (demographic 16+).
- **Frictionless Checkout:** Minimize clicks between product discovery and order placement; complete checkout in 3 steps or fewer.
- **Prominent Trust Signals:** Verification badges for approved sellers, verified purchase tags on reviews, and clear ratings visible at every touchpoint.
- **Role-Based Interface Isolation (Buyer vs Seller vs Admin):**
  - **Strict Visual & Navigational Separation:** The customer Storefront (Buyer), Seller Studio (Merchant), and Admin Panel (Oversight) MUST NEVER be commingled or displayed simultaneously in the primary navigation.
  - **Buyer Storefront Sanctity:** Shoppers browsing the boutique must experience an uncluttered, high-end editorial storefront without merchant or administrative controls ("Seller Studio", "Oversight") in the header.
  - **Role-Gated Portals:**
    - Storefront (`/`): Publicly accessible to all visitors and customers.
    - Seller Studio (`/seller`): Strictly gated to authenticated users with `role: 'seller'`. Accessed via user account dropdown ("Atelier Dashboard") or discreet footer application link ("Sell with Us").
    - Admin Panel (`/admin`): Strictly gated to authenticated users with `role: 'admin'`.

## 4. Obsidian Memory Protocols
- Always follow the **Hierarchy of Truth**: Source code is always authoritative over memory notes.
- Use targeted semantic retrieval; never load the entire vault into the context window.
- Consolidate and update existing notes progressively rather than generating fragmented duplicates.
- Mark confidence states (`VERIFIED`, `INFERRED`, `PROPOSED`, `HISTORICAL`, `OBSOLETE`).

## Related Links
- [[CORE_MEMORY]]
- [[Apparel_Marketplace_Requirements_and_Design_Plan]]
- [[Functional_Requirements]]
- [[ADR-007_Framer_Motion_and_Swiper_for_Frontend_Experience]]
- [[ADR-008_The_Rail_and_The_Rack_Storefront_Design_System]]
- [[CURRENT_STATE]]
