# Stripe Webhook Signature Verification Gotchas

Status: VERIFIED
Last Updated: 2026-09-23
Tags: #problem-solution #stripe #webhooks #security

## Problem
Incoming Stripe webhooks return `400 Bad Request: Invalid signature` despite having the correct `STRIPE_WEBHOOK_SECRET` configured in `.env`.

## Root Cause
1. **Modified Request Payload:** Laravel middlewares that trim strings (`TrimStrings`) or convert empty strings to null (`ConvertEmptyStringsToNull`) mutate `$request->all()`. If the developer passes `$request->getContent()` after middleware modifications, or passes `$request->all()`, the HMAC hash does not match Stripe's computed signature.
2. **CSRF Token Mismatch:** If the route is placed in `routes/web.php` without CSRF exemption, Laravel returns a `419 Page Expired` before reaching the controller.
3. **Mismatched Webhook Secret:** Local testing with Stripe CLI (`stripe listen --forward-to ...`) generates an ephemeral signing secret (`whsec_...`) distinct from the Stripe Dashboard secret.

## Solution / Prevention
1. Place webhook routes in `routes/api.php` where CSRF is not applied.
2. In the controller, use `$request->getContent()` directly:
   ```php
   $payload = $request->getContent();
   $sigHeader = $request->header('Stripe-Signature');
   $secret = config('services.stripe.webhook_secret');
   $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
   ```
3. Exclude `/api/v1/webhooks/stripe` from any request-altering middleware.

## Related Links
- [[CORE_MEMORY]]
- [[Stripe_Payment_Integration_and_Webhooks]]
- [[Payment_Pipeline]]
