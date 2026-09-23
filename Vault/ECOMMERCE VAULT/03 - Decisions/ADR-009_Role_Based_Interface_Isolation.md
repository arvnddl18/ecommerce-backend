# ADR-009: Strict Role-Based Interface Isolation (Buyer vs Seller vs Admin)

Status: VERIFIED
Date: 2026-09-23
Deciders: Arvin & Engineering Team
Tags: #adr #architecture #security #rbac #frontend #ux #verified

---

## Context & Problem Statement
In a multi-vendor apparel e-commerce marketplace, the platform serves three distinct user roles:
1. **Buyers (Shoppers):** Unauthenticated visitors or authenticated consumers browsing garments, assembling a shopping bag, and completing checkout.
2. **Sellers (Merchants / Ateliers):** Authenticated shop owners managing apparel listings, variants, stock levels, fulfillment statuses, and Stripe Connect payouts.
3. **Platform Administrators (Oversight):** Privileged operators managing vendor approvals, dispute arbitration, and platform metrics.

During initial prototype styling, "The Collective" (Storefront), "Seller Studio", and "Oversight" were rendered as parallel tabs in the main navigation header for developer convenience. However, displaying buyer and seller interfaces simultaneously or placing seller/admin controls in the customer storefront navigation violates basic e-commerce UX, destroys consumer trust ("Curation Halo Effect"), increases cognitive clutter, and compromises visual role boundaries.

## Decision
We enforce strict **Role-Based Interface Isolation**:

1. **Buyer Storefront Sanctity:**
   - The primary navigation header (`Navbar.tsx`) is exclusively reserved for customer shopping discovery (Categories/Rail, Wishlist, Shopping Bag, User Account).
   - "Seller Studio" and "Admin Oversight" controls are strictly eliminated from the buyer navigation.
   - Public visitors only see a subtle, conventional footer link (e.g. "Atelier Onboarding" / "Sell with Us") or an option within the authenticated user profile dropdown.

2. **Dedicated Role-Gated Portals:**
   - **Seller Studio (`/seller`):** Accessible only to authenticated users with `role: 'seller'`. Entering Seller Studio transitions the interface into a dedicated merchant portal layout with atelier-specific navigation and an explicit "Exit to Storefront" action. Non-sellers attempting to access this route are redirected to the atelier onboarding/application page.
   - **Admin Command Center (`/admin`):** Accessible strictly to authenticated users with `role: 'admin'`. Non-admins are denied access.

3. **Client-Side Surface Gating:**
   - State and surface switching must respect `user.role` from `AuthContext`.
   - Normal buyers browsing the marketplace are never shown merchant management tools.

## Consequences

### Positive
- **Brand Trust & Elevated Aesthetic:** The customer storefront remains pristine, editorial, and focused on garments without internal administrative distraction.
- **Security & Authorization Alignment:** UI presentation strictly mirrors backend Sanctum and Policy authorization (`isSeller()`, `isAdmin()`).
- **Focused Workflows:** Sellers manage catalog inventory in an unencumbered workspace, and buyers shop without merchant tools cluttering their view.

### Negative / Trade-offs
- Developers can no longer flip between seller and buyer views with a single header click without logging into accounts with the corresponding roles (or using designated dev mode flags).

## Related Links
- [[CORE_MEMORY]]
- [[Developer_and_System_Preferences]]
- [[Frontend_Design_System_Architecture]]
- [[Apparel_Marketplace_Requirements_and_Design_Plan]]
