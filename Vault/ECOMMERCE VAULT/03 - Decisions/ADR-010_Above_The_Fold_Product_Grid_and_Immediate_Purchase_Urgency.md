# ADR-010: Above-The-Fold Product Grid and Immediate Purchase Urgency

Status: VERIFIED
Date: 2026-09-23
Deciders: Arvin & Design Team
Tags: #adr #architecture #ux #conversion #frontend #design-system #fold #verified

---

## Context & Problem Statement
In the previous storefront design, an oversized editorial hero section (`.intro-section`, min-height 430px) displayed large display headlines ("The Tactile Wardrobe") and multi-paragraph mission copy. 

As a consequence, **zero products or prices were visible above the fold** on standard 1080p desktop and mobile viewports. Shoppers landing on the boutique were confronted with abstract prose rather than visual merchandise, depriving them of the immediate tactile urge to buy, adding cognitive drag, and hindering rapid garment discovery.

## Decision
We replace the oversized hero banner with an **Immediate Above-the-Fold Catalog & 4-Column Grid** matching the canonical `references/index.html` and `references/style.css` specifications:

1. **Compact Shop Heading (`.shop-heading`):**
   - Replaces the 430px text block with a sleek, low-profile headline:
     - Left: 10px uppercase `eyebrow` ("New arrivals") + clamp display headline ("Shop the latest").
     - Right: Subtle value note ("Fresh pieces, ready to wear. Updated weekly.") + interactive Sort dropdown ("Sort: Featured ⌄").
   - Max vertical footprint capped under 150px.

2. **Streamlined Category Rail (`.rack-wrap`):**
   - Renders immediately below the heading (`Shop by category` + `All pieces 48`, `Outerwear 12`, `Tops 18`, etc.).

3. **Catalog Toolbar & Rapid Filters (`.catalog-top`):**
   - Left: Dynamic listing count (`{count} items · Showing {category}`).
   - Right: Instant client-side filters (`All`, `In stock`, `Under $150`).

4. **4-Column Product Grid Visible Above the Fold (`.product-grid`):**
   - Grid layout: `repeat(4, 1fr)` (3-column on tablet, 2-column on mobile).
   - High-fidelity apparel photography with `.78` aspect ratio (`.68` for tall silhouettes).
   - **Slide-up Quick Add:** `＋ Quick add` button slides up on hover (`transform: translateY(0)`), allowing 1-click addition to bag.
   - **Social Proof & Ratings:** Gold star rating display (`★★★★★ 4.9 (128)`) directly beneath product title and atelier attribution.
   - Scarcity badges: `New` (warm highlight yellow) and `Low stock` (coral accent).

5. **Relocated Editorial Story (`.story-section`):**
   - Brand storytelling ("Why FOLD", "Less, but better chosen.") is positioned directly *below* the product grid, serving buyers who wish to understand the atelier philosophy after inspecting the catalog.

## Consequences

### Positive
- **Immediate Buying Urge:** 4 to 8 physical garments, prices, and reviews appear instantly upon page load without scrolling.
- **Maximized Browsing Efficiency:** Shoppers can filter, sort, quick-add, or click to inspect products within the first 3 seconds of visiting.
- **Conversion-Optimized Hierarchy:** Merchandise and buy signals take precedence over decorative corporate text.
- **Faithful Alignment:** 100% fidelity with the reference mockup in `references/index.html` and `references/style.css`.

### Negative / Trade-offs
- Less room for long-form narrative copy on the very first screen (moved to the story section and product detail hangtags).

## Related Links
- [[CORE_MEMORY]]
- [[Developer_and_System_Preferences]]
- [[Frontend_Design_System_Architecture]]
- [[ADR-008_The_Rail_and_The_Rack_Storefront_Design_System]]
- [[ADR-009_Role_Based_Interface_Isolation]]
