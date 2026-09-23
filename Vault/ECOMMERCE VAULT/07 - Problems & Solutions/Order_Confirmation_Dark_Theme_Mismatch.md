# Order Confirmation Dark Theme Mismatch and Illegible Text

Status: VERIFIED
Last Updated: 2026-09-24
Tags: #problem-solution #frontend #ui-ux #the-rail-and-the-rack #order-success #tailwind

## Problem
Upon completing Stripe Checkout and redirecting to `/order/success`, the customer encountered:
1. **Completely Illegible Text:** "Payment Confirmed!" and descriptive copy rendered in pure white (`text-white`) and faint slate (`text-slate-400`) on a warm off-white (`#FAFAF8`) background, making headings virtually invisible.
2. **Visual Clashing:** A heavy dark navy/slate container (`bg-slate-900 border border-slate-800`) with fluorescent green indicators and a generic indigo pill button (`bg-indigo-600`) abruptly appeared, violating the editorial boutique design system ("The Rail & The Rack").
3. **Mismatched Status State:** The order slip claimed "Payment Confirmed" and "Verified via Stripe Webhook HMAC" even while displaying `Status: PENDING` prior to asynchronous webhook processing.
4. **Black-on-Black Button Text:** The "Return to Catalog" button rendered as a solid, illegible dark rectangle where both the text label and arrow icon were invisible.
5. **Non-Functional Navigation ("Return to Catalog" Click Did Nothing):** Clicking "Return to Catalog" failed to return to the catalog storefront, leaving the customer trapped on `/order/success`.

## Root Cause
1. **Legacy Dark-Mode Utility Classes:** `OrderSuccess.tsx` and `OrderCancel.tsx` were lingering legacy templates from an early prototype that relied on dark-mode utility classes (`text-white`, `bg-slate-900`, `bg-indigo-600`).
2. **Obsolete Root Layout Attribute:** The root Blade layout (`resources/views/app.blade.php`) retained an obsolete `class="dark"` attribute on the `<html>` element.
3. **Unlayered CSS Specificity Clash in Tailwind v4:** In `resources/css/app.css`, raw base rules (`button { font: inherit; color: inherit; }`) were declared outside any `@layer`. Under the CSS Cascade Layers specification, unlayered styles always override layered styles (such as Tailwind v4's `@layer utilities` `.text-white`), regardless of selector specificity. Because the document body has `color: var(--ink)` (`#1A1A1A`), `<button className="bg-[#1A1A1A] text-white">` inherited `#1A1A1A` text color onto a `#1A1A1A` background, rendering the label and icon completely invisible.
4. **Non-Reactive SPA Routing State:** In `resources/js/app.tsx`, `const pathname = window.location.pathname;` was evaluated once on mount as a static variable rather than stored in React state. When `navigateToCatalog()` called `window.history.pushState({}, '', '/')`, React had no reason to re-render because `currentSurface` and `selectedCategory` were already at default values, and no `popstate` event listener was wired up to re-evaluate `pathname`.

## Solution
1. **Redesigned `OrderSuccess.tsx` & `OrderCancel.tsx` to "The Rail & The Rack" Design System:**
   - **Boutique Docket Slip:** Replaced dark card with a crisp white packing docket (`bg-white border border-[#E8E6E1]`).
   - **Contrasting Typography:** Headlines use `Space Grotesk` in `#1A1A1A`, secondary copy in `#6B6B6B`, and order reference numbers in uppercase monospace with wide letter tracking (`font-mono tracking-wider`).
   - **Dynamic Status Polling & Real-Time Sync:** Added polling (up to 6 attempts every 2.5s) to automatically transition status from `pending` (amber badge) to `paid` / `settled` (green badge) as soon as Stripe's webhook arrives.
2. **Cascaded Layer Protection (`resources/css/app.css`):**
   - Wrapped base element resets (`*`, `html`, `body`, `a`, `button`) inside `@layer base { ... }`. This allows utility classes like `text-white` in `@layer utilities` to take precedence naturally over `color: inherit`.
   - Enhanced `OrderSuccess.tsx` button with explicit `text-white` on the child `<ArrowLeft>` and `<span className="text-white">` elements, high-contrast monospace typography (`font-mono tracking-[0.16em] uppercase font-semibold`), interactive hover translation, and added an accompanying secondary `Print Receipt` button.
3. **Reactive SPA Navigation in `app.tsx` & `Navbar.tsx`:**
   - Converted `pathname` to a reactive state hook: `const [pathname, setPathname] = useState<string>(() => window.location.pathname);`.
   - Wired up a `popstate` event listener via `useEffect` to keep React state in sync with browser forward/back buttons.
   - Updated `navigateToCatalog`, `handleSurfaceChange`, and `Navbar` to programmatically update `setPathname('/')` and push state, guaranteeing immediate re-render and smooth scroll to top when returning to the catalog.

## Related Links
- [[CORE_MEMORY]]
- [[ADR-008_The_Rail_and_The_Rack_Storefront_Design_System]]
- [[Developer_and_System_Preferences]]
- [[CURRENT_STATE]]
