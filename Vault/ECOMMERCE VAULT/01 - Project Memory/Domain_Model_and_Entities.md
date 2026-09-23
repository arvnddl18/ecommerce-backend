# Domain Model & Core Entities

Status: VERIFIED
Last Updated: 2026-09-23
Tags: #domain #models #database

## 1. Entities
1. **User / Customer:**
   - Authenticated via Laravel Sanctum API tokens.
   - Holds profile, shipping addresses, order history.
2. **Product & Category:**
   - Catalog hierarchy with SKU, title, description, price (in integer cents), stock quantity.
3. **Cart & Cart Item:**
   - Ephemeral shopping cart associated with a user or guest token.
4. **Order & Order Item:**
   - Immutable snapshot of products, quantities, and prices captured at purchase time.
   - Order statuses: `pending`, `paid`, `processing`, `shipped`, `cancelled`, `refunded`.
5. **Payment Transaction / Webhook Event:**
   - Stores Stripe Session ID, Payment Intent ID, payment status, and raw JSONB webhook event payloads for auditability.

## 2. Related Links
- [[CORE_MEMORY]]
- [[Data_Flow_and_Storage]]
- [[ADR-002_PostgreSQL_16_over_MySQL]]
