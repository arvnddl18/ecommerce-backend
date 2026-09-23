# Payment Pipeline

Status: VERIFIED
Last Updated: 2026-09-23
Tags: #architecture #stripe #payments

## 1. Flow Overview
```mermaid
sequenceDiagram
    autonumber
    actor Customer as User (Browser)
    participant Front as React Frontend
    participant API as Laravel App
    participant Stripe as Stripe API
    participant DB as PostgreSQL
    participant Worker as Redis Queue Worker

    Customer->>Front: Click "Checkout"
    Front->>API: POST /api/v1/checkout/session (Cart ID)
    API->>DB: Lock stock & insert pending Order
    API->>Stripe: Create Stripe Checkout Session
    Stripe-->>API: Return Checkout URL & Session ID
    API-->>Front: JSON { checkout_url: "..." }
    Front->>Customer: Redirect to Stripe Hosted Checkout
    Customer->>Stripe: Enters Card & Completes Payment
    Stripe->>Customer: Redirect to /order/success
    Stripe->>API: POST /api/v1/webhooks/stripe (Event payload)
    API->>API: Verify HMAC Signature Header
    API->>Worker: Dispatch WebhookFulfillmentJob
    API-->>Stripe: Immediate 200 OK
    Worker->>DB: Update Order to "paid", commit stock deduction
```

## 2. Key Safeguards
- **Zero Cardholder Data:** Server never touches raw card data; handled entirely by Stripe.
- **HMAC Signature Check:** Protects webhook endpoint from forged requests.
- **Idempotency Keys:** Payment session creation and webhook handlers log unique event IDs to prevent duplicate processing on network retries.

## 3. Related Links
- [[CORE_MEMORY]]
- [[Stripe_Payment_Integration_and_Webhooks]]
- [[System_Architecture]]
