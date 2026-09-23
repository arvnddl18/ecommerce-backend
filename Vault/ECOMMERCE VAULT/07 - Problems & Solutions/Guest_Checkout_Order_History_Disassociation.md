# Guest Checkout and Unlinked Order History Disassociation

Status: VERIFIED
Last Updated: 2026-09-24
Tags: #problem-solution #orders #stripe-checkout #sanctum #auth #order-history

## Problem
After completing a Stripe checkout payment for an order (e.g., Order #`ORD-VRKT44WOXM` for ₱95.00), a logged-in buyer opened the **Order History** modal (`BuyerOrderHistoryModal.tsx`) and was greeted with:
> "No orders placed yet. Your receipts, shipping statuses, and order line items will appear here."

Even though the order was successfully created, settled in Stripe, and saved in the database with status `paid`, the order was missing from the customer's order history.

## Root Cause
Three compounding factors caused the order to be disassociated from the buyer's account:

1. **Missing Authorization Header in Frontend Checkout Session Request:**
   In `resources/js/components/CartDrawer.tsx` (`handleInitiateStripeCheckout`), the `fetch('/api/v1/checkout/session')` call only passed `Content-Type`, `Accept`, and `X-Cart-Token`. It did NOT attach `Authorization: Bearer ${authToken}` even when the user was actively logged in.
2. **Strict Reliance on Sanctum Guard in Controller without Email Fallback:**
   In `app/Http/Controllers/Api/v1/CheckoutController.php`, `$user = $request->user('sanctum')` evaluated to `null` because the Authorization header was omitted. The controller did not check whether `customer_email` matched an existing user in the database, resulting in `$order->user_id` being recorded as `null`.
3. **Rigid User ID Query in Order Controller:**
   In `app/Http/Controllers/Api/v1/OrderController.php`, the `index()` and `show()` actions strictly queried `Order::where('user_id', $user->id)`. Any order placed as guest or without an initial auth token link had `user_id = null`, making them invisible to the buyer even though `customer_email` matched the authenticated account. Furthermore, `ReviewController.php` verified-purchase checks also missed these unlinked orders.

## Solution

1. **Frontend Auth Token Attachment (`CartDrawer.tsx`):**
   Destructured `token` from `useAuth()` (with fallback to `localStorage.getItem('auth_token')`) and attached the `Authorization: Bearer ${authToken}` header to the `/api/v1/checkout/session` request when available.
2. **Customer Email User Fallback (`CheckoutController.php`):**
   Added an email-matching fallback during checkout session creation:
   ```php
   $user = $request->user('sanctum');
   if (! $user && ! empty($validated['customer_email'])) {
       $user = User::where('email', $validated['customer_email'])->first();
   }
   ```
3. **Automatic Order Reconciliation & Resilient Matching (`OrderController.php`):**
   - In `index()`: Automatically updates unlinked orders (`user_id IS NULL`) matching the authenticated user's email to associate them with the buyer's `user_id`. Also queries using `where('user_id', $user->id)->orWhere('customer_email', $user->email)`.
   - In `show()`: Allows access if `$order->user_id === $user->id || $order->customer_email === $user->email` and reconciles `user_id`.
4. **Verified Purchaser Check Resilience (`ReviewController.php`):**
   Updated `ReviewController::store` to check verified purchase eligibility by either `user_id` or `customer_email`.
5. **Database Healing & Feature Test Coverage:**
   - Healed existing database records so orders placed with `arvnddl18@gmail.com` are linked to User ID 17.
   - Added automated feature test `test_buyer_can_view_orders_placed_with_their_email_when_originally_unlinked` in `tests/Feature/BuyerOrderHistoryTest.php` (all 79 PHPUnit tests passing).

## Related Links
- [[CORE_MEMORY]]
- [[Buyer_Order_History_and_Review_Pipeline]]
- [[Order_Confirmation_Dark_Theme_Mismatch]]
- [[ADR-001_Laravel_11_Headless_REST_API]]
