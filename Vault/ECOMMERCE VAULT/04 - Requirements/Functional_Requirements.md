# Functional Requirements

Status: VERIFIED
Last Updated: 2026-09-23
Tags: #requirements #specs

## 1. Authentication & Users
- User registration and login issuing Laravel Sanctum Bearer tokens.
- Secure password hashing via Argon2id / Bcrypt.
- Protected profile management and past order history lookup.

## 2. Product Catalog
- Hierarchical categories and product listing endpoints with pagination, filtering, and sorting.
- Product detail retrieval with stock availability check.

## 3. Shopping Cart
- Stateless / authenticated cart operations: Add item, update quantity, remove item, clear cart.
- Server-side validation of stock limits before cart updates.

## 4. Checkout & Stripe Payments
- Generate Stripe Checkout session with line items and redirect URL.
- Inventory reservation during checkout window.
- Ingestion of Stripe webhooks (`checkout.session.completed`, `payment_intent.payment_failed`, `charge.refunded`).

## 5. Orders & Fulfillment
- Immutable order records created upon successful payment verification.
- Asynchronous dispatch of confirmation emails via background queue.

## Related Links
- [[CORE_MEMORY]]
- [[Payment_Pipeline]]
- [[Non_Functional_Requirements]]
