# Stripe Payment Integration and Webhooks

Status: VERIFIED (Webhook Signature & Job Dispatch) · Spec (Redis Worker)
Last Updated: 2026-09-23
Tags: #knowledge #stripe #payments #security #calibrated

## 1. Webhook Signature Verification in Laravel
When receiving incoming Stripe webhook payloads at `/api/v1/webhooks/stripe`, Stripe sends a signature in the `Stripe-Signature` HTTP header.

### Critical Implementation Details:
- **Raw Payload Required:** `\Stripe\Webhook::constructEvent($payload, $sigHeader, $secret)` requires the **raw, unparsed HTTP request body** (`$request->getContent()`). If Laravel's JSON middleware has modified the string or if whitespace differs, HMAC verification fails immediately.
- **CSRF Exemption:** Webhook routes must be registered in `routes/api.php` or exempted from CSRF middleware in `bootstrap/app.php`.
- **Immediate ACK:** Respond with HTTP `200 OK` within 2–3 seconds; do not perform heavy database writes or send emails synchronously in the controller.

```php
try {
    $event = \Stripe\Webhook::constructEvent(
        $request->getContent(),
        $request->header('Stripe-Signature'),
        config('services.stripe.webhook_secret')
    );
} catch (\UnexpectedValueException $e) {
    return response()->json(['error' => 'Invalid payload'], 400);
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    return response()->json(['error' => 'Invalid signature'], 400);
}

// Dispatch to queue worker (synchronous locally via QUEUE_CONNECTION=sync, Redis in Docker/production)
ProcessStripeWebhookJob::dispatch($event->toArray());

return response()->json(['status' => 'success']);
```

## 2. Idempotency Key Handling
- Stripe guarantees at-least-once delivery for webhooks.
- Store processed event IDs (`$event->id`, e.g. `evt_1Oxxx`) in a `webhook_events` database table with a unique constraint. If an event ID already exists, acknowledge with `200 OK` and skip re-execution.

## Related Links
- [[CORE_MEMORY]]
- [[Payment_Pipeline]]
- [[Stripe_Webhook_Signature_Verification_Gotchas]]
