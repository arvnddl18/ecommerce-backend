<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessStripeWebhookJob;
use App\Models\WebhookEvent;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __construct(
        protected StripeService $stripeService
    ) {}

    /**
     * Handle incoming Stripe webhook notifications with HMAC signature validation and idempotency.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = $this->stripeService->constructWebhookEvent($payload, $sigHeader);
        } catch (UnexpectedValueException $e) {
            Log::warning('Stripe webhook invalid payload', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $eventId = $event->id;
        $eventType = $event->type;

        // Idempotency check: Acknowledge and skip if already received
        $existing = WebhookEvent::where('stripe_event_id', $eventId)->first();
        if ($existing) {
            Log::info("Duplicate Stripe webhook event ignored: {$eventId}");

            return response()->json([
                'received' => true,
                'status' => 'already_processed',
            ], 200);
        }

        // Record incoming webhook for audit and idempotency
        WebhookEvent::create([
            'stripe_event_id' => $eventId,
            'type' => $eventType,
            'payload' => $event->toArray(),
            'processed_at' => null,
        ]);

        // Dispatch asynchronous processing job to Redis queue
        ProcessStripeWebhookJob::dispatch($event->toArray());

        return response()->json([
            'received' => true,
            'event_id' => $eventId,
        ], 200);
    }
}
