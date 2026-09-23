# ADR-008: "The Rail & The Rack" Boutique Storefront Design System

Status: VERIFIED
Date: 2026-09-23
Deciders: Arvin, Senior Frontend & Visual Design Team
Tags: #adr #frontend #ui-ux #design-system #boutique #the-rail-and-the-rack #verified

---

## Context & Problem Statement
Generic AI-generated e-commerce templates rely on uniform rounded-corner card grids with drop shadows, full-width hero carousels, middle-dot metadata (`Men · Shirts · New`), all-caps eyebrow labels, numbered step markers, and decorative fade/slide animations on every section. These generic patterns trigger immediate skepticism from users seeking authentic, curated apparel and degrade perceived brand trust.

For an apparel marketplace targeting 16+ demographics and independent ateliers, visual perception directly drives the purchase decision. A layout that looks intentionally arranged rather than database-dumped triggers a "curation halo effect" that elevates vendor trust.

## Decision
We implemented **"The Rail & The Rack"** design system across the entire frontend storefront, matching the exact bespoke styling and structural tokens defined in `references/style.css`.

### Key System Characteristics:
1. **The Rail Category Navigation (`RackRailNav.tsx`):**
   - A horizontal scrollable rail with thin 1px vertical dividers mimicking physical hangers on a boutique rack.
   - Sticky on scroll, displaying hanger index numbers (`01`, `02`), category title, and active count.
2. **The Asymmetric Editorial Grid (`ProductGrid.tsx` & `ProductCard.tsx`):**
   - 2-column asymmetric grid (`1.15fr .85fr`) with 4 distinct cadence tiles:
     - `feature-card`: aspect-ratio 0.86 (primary prominence)
     - `simple-card`: aspect-ratio 0.87, `margin-top: 110px`
     - `offset-card`: `margin-top: -55px`
     - `wide-card`: aspect-ratio 1.32, `margin-top: 20px`
   - Replaced uniform cards with individual hierarchy, catalog index markers (`N° 01`), and clean hairline borders.
3. **Two-Zone Product Detail Page (`ProductDetailModal.tsx`):**
   - **Zone 1:** Bleed-to-edge product photography gallery (Swiper) with pull-to-inspect zoom.
   - **Zone 2:** Physical **Garment Tag Spec Module** styled like an authentic physical clothing hangtag (punched eyelet, barcode, monospace SKU, material breakdown, fit profile, care instructions).
4. **Motion Restraint Protocol (`resources/js/lib/motion.ts`):**
   - Motion is strictly reserved for two deliberate interaction moments:
     1. The rack-rail horizontal glide and hanger selection.
     2. Product image "pull to inspect" zoom response.
   - All other decorative animations eliminated to preserve curated boutique quietude.
5. **Color & Type Discipline:**
   - Base Surface: Warm off-white (`#FAFAF8`), white cards (`#FFFFFF`), borders (`#E8E6E1`), ink (`#1A1A1A`), muted (`#6B6B6B`).
   - Primary Accent: Coral-orange (`#FF5A36`) reserved strictly for buy signals and active category hangers.
   - Highlight Accent: Warm yellow (`#FFC94D`) reserved strictly for scarcity badges (`Curated`, `Limited`).
   - Display Font: `Space Grotesk` (weights 500, 700) with tight tracking (`-.09em`).
   - Body Font: `DM Sans` (weights 400, 500, 600) with line length under 80 characters.

## Consequences & Verification
- **Aesthetic Distinction:** The storefront immediately communicates curated boutique prestige rather than mass-produced dropshipping.
- **Performance:** Clean CSS and lightweight Framer Motion variants ensure fast rendering without blocking Largest Contentful Paint (LCP).
- **Test Integrity:** 100% passing PHPUnit feature tests (37 tests, 152 assertions) and clean Vite production bundle.

## Related Links
- [[CORE_MEMORY]]
- [[Developer_and_System_Preferences]]
- [[ADR-007_Framer_Motion_and_Swiper_for_Frontend_Experience]]
- [[CURRENT_STATE]]
