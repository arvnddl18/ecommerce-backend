# Seller Gross Sales vs Stripe Dashboard Reflected Amount Mismatch

Status: VERIFIED (Root Cause Confirmed via Database & Stripe API Inspection)
Date: 2026-09-24
Tags: #problem #solution #stripe #marketplace #seller-dashboard #payments #calibrated

---

## 1. Problem Description
In the Atelier Studio (Seller Dashboard) for **Solace Essentials**, the Gross Sales card reflected **₱96.00** ("Live Stripe Connect earnings") and 2 Pieces Dispatched. However, checking the Stripe Dashboard showed **₱0.00 / no reflected balance** for this seller.

---

## 2. Root Cause Analysis

### A. Gross Sales Calculation Included Unpaid Pending Orders
In [SellerController.php](file:///c:/arvincodework/ecommerce-backend/app/Http/Controllers/Api/v1/SellerController.php#L43-L47):
```php
$orderItems = OrderItem::where('seller_id', $seller->id)
    ->whereHas('order', fn ($q) => $q->where('status', '!=', 'cancelled'))
    ->get();

$totalRevenue = $orderItems->sum('total_price');
```
- The backend query checked `where('status', '!=', 'cancelled')` instead of restricting to completed `where('status', Order::STATUS_PAID)`.
- Solace Essentials (`seller_id: 3`) had items in two orders:
  1. **Order #1 (`ORD-QBDDZ1YHLV`):** ₱48.00 (4,800 cents) — `status: pending`, `stripe_session_id: null`.
  2. **Order #2 (`ORD-GP7KAOOV3Q`):** ₱48.00 (4,800 cents) — `status: pending`, `stripe_session_id: cs_test_b1h5Lof0GC8Plw1WNc1QEHjeb7cL2XjuvmQStYuMF7S1EO4zo89HEiwlv4`.
- Because neither order was marked `cancelled`, the sum was `4800 + 4800 = 9600 cents` (**₱96.00**).

### B. Stripe Checkout Session Was Never Paid
Querying the live Stripe API for session `cs_test_b1h5Lof0GC8Plw1WNc1QEHjeb7cL2XjuvmQStYuMF7S1EO4zo89HEiwlv4`:
- `status`: `open`
- `payment_status`: `unpaid`
- `payment_intent`: `null`
The test customer navigated to Stripe Checkout but abandoned the session without entering card details or completing payment.

### C. Seeded Seller Uses Mock Stripe Connect ID
In [ProductSeeder.php:76](file:///c:/arvincodework/ecommerce-backend/database/seeders/ProductSeeder.php#L76):
- Solace Essentials' `stripe_account_id` was seeded as `"acct_solace_demo789"`.
- This is a placeholder mock string, not an authentic Stripe Connected Express/Custom Account under platform `acct_1UIsAnLs7Pzve8JW`. Real transfers cannot route to mock account strings.

### D. Local Stripe Webhook Listener Inactive
- Although test purchases were completed for other sellers (Order #4 for ₱90.00 and Order #5 for ₱75.00), those orders remained in `status: pending` because local webhook delivery was not active (`webhook_events` table had 0 rows).
- Stripe webhooks (`checkout.session.completed`) require `stripe listen --forward-to localhost:8000/api/v1/stripe/webhook` to notify [StripeWebhookController.php](file:///c:/arvincodework/ecommerce-backend/app/Http/Controllers/Api/v1/StripeWebhookController.php) and execute [ProcessStripeWebhookJob.php](file:///c:/arvincodework/ecommerce-backend/app/Jobs/ProcessStripeWebhookJob.php).

---

## 3. Verified Fix & Recommendations

1. **Refactor Seller Dashboard Metric Query:**
   Update [SellerController.php](file:///c:/arvincodework/ecommerce-backend/app/Http/Controllers/Api/v1/SellerController.php#L43-L47) to only aggregate orders with `status === Order::STATUS_PAID`:
   ```php
   $orderItems = OrderItem::where('seller_id', $seller->id)
       ->whereHas('order', fn ($q) => $q->where('status', Order::STATUS_PAID))
       ->get();
   ```

2. **Run Stripe CLI Webhook Tunnel for Local End-to-End Testing:**
   ```bash
   stripe listen --forward-to localhost:8000/api/v1/stripe/webhook
   ```
   Set `STRIPE_WEBHOOK_SECRET` in `.env` to the signing secret (`whsec_...`) printed by the CLI.

3. **Onboard Live Stripe Connected Accounts:**
   For testing real Connect transfers and seller payouts, replace mock account IDs (`acct_solace_demo789`) with actual Stripe Connect test accounts generated via Stripe Dashboard or Stripe Connect onboarding.

4. **Direct Order Status Verification on Success Callback:**
   In [CheckoutController.php::getOrderStatus](file:///c:/arvincodework/ecommerce-backend/app/Http/Controllers/Api/v1/CheckoutController.php#L150-L205), when a customer arrives at `/order/success`, the backend proactively queries Stripe API directly to verify payment completion and immediately fulfills the order, eliminating reliance on local webhook tunnels.

---

## 4. Related Links
- [[CORE_MEMORY]]
- [[Payment_Pipeline]]
- [[ADR-006_Stripe_Connect_for_Multi_Seller_Payouts]]
- [[Stripe_Payment_Integration_and_Webhooks]]
- [[Stripe_Webhook_Signature_Verification_Gotchas]]
