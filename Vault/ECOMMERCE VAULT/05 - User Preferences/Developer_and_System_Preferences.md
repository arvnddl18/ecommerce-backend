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
- **"The Rail & The Rack" Boutique Identity ([[ADR-008_The_Rail_and_The_Rack_Storefront_Design_System]]):**
  - Reject generic AI templates (no uniform drop-shadow card grids, no full-width hero carousels, no middle-dot meta strings, no arrow glyphs, no numbered step circles for non-sequences).
  - Use horizontal scrollable **rack-rail** category navigation with thin 1px dividers like clothing hangers.
  - Implement an **asymmetric editorial 2-column grid (`1.15fr .85fr`)** with varied aspect ratios (`0.86`, `0.87`, `1.32`) and staggered vertical offsets.
  - Two-zone product detail view: Bleed photography gallery + compact **garment tag module** styled as a physical hangtag (material, fit, care, barcode).
  - **Color Tokens:** Warm off-white (`#FAFAF8`) base surface, pure white cards (`#FFFFFF`), hairline borders (`#E8E6E1`), primary coral-orange accent (`#FF5A36`) reserved strictly for buy actions, highlight warm yellow (`#FFC94D`) for scarcity badges.
  - **Typography:** Display headlines in `Space Grotesk` (weights 500, 700) with tight tracking (`-.09em`), paired with body and metadata in clean `DM Sans`.
  - **Motion Restraint:** Motion is strictly reserved for two deliberate moments: rack-rail category glide and pull-to-inspect image zoom.
- **Mobile-First Orientation:** Tailor layouts and hit targets for mobile shoppers (demographic 16+).
- **Frictionless Checkout:** Minimize clicks between product discovery and order placement; complete checkout in 3 steps or fewer.
- **Prominent Trust Signals:** Verification badges for approved sellers, verified purchase tags on reviews, and clear ratings visible at every touchpoint.

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
