<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Product;
use App\Models\WebhookEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessStripeWebhookJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  array<string, mixed>  $eventData
     */
    public function __construct(
        public array $eventData
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $eventType = $this->eventData['type'] ?? '';
        $dataObject = $this->eventData['data']['object'] ?? [];
        $eventId = $this->eventData['id'] ?? null;

        Log::info("Processing Stripe webhook job for event: {$eventType}", ['event_id' => $eventId]);

        match ($eventType) {
            'checkout.session.completed' => $this->handleCheckoutSessionCompleted($dataObject),
            'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($dataObject),
            'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($dataObject),
            'charge.refunded' => $this->handleChargeRefunded($dataObject),
            default => Log::info("Unhandled Stripe event type: {$eventType}"),
        };

        if ($eventId) {
            WebhookEvent::where('stripe_event_id', $eventId)->update([
                'processed_at' => now(),
            ]);
        }
    }

    /**
     * Handle checkout.session.completed event.
     *
     * @param  array<string, mixed>  $session
     */
    protected function handleCheckoutSessionCompleted(array $session): void
    {
        $sessionId = $session['id'] ?? null;
        $orderId = $session['metadata']['order_id'] ?? null;
        $orderNumber = $session['metadata']['order_number'] ?? null;
        $paymentIntentId = $session['payment_intent'] ?? null;

        $order = null;

        if ($orderId) {
            $order = Order::find($orderId);
        } elseif ($sessionId) {
            $order = Order::where('stripe_session_id', $sessionId)->first();
        } elseif ($orderNumber) {
            $order = Order::where('order_number', $orderNumber)->first();
        }

        if (! $order instanceof Order) {
            Log::warning("Order not found for Stripe checkout session: {$sessionId}");

            return;
        }

        if ($order->status === Order::STATUS_PAID) {
            Log::info("Order #{$order->order_number} already marked as paid.");

            return;
        }

        DB::transaction(function () use ($order, $paymentIntentId): void {
            $order->update([
                'status' => Order::STATUS_PAID,
                'stripe_payment_intent_id' => $paymentIntentId ?? $order->stripe_payment_intent_id,
            ]);

            // Deduct inventory for purchased items
            foreach ($order->items as $item) {
                if ($item->product_id) {
                    Product::where('id', $item->product_id)
                        ->where('stock', '>=', $item->quantity)
                        ->decrement('stock', $item->quantity);
                }
            }
        });

        Log::info("Order #{$order->order_number} successfully fulfilled and paid.");
    }

    /**
     * Handle payment_intent.succeeded event.
     *
     * @param  array<string, mixed>  $paymentIntent
     */
    protected function handlePaymentIntentSucceeded(array $paymentIntent): void
    {
        $paymentIntentId = $paymentIntent['id'] ?? null;
        $orderId = $paymentIntent['metadata']['order_id'] ?? null;

        if (! $paymentIntentId && ! $orderId) {
            return;
        }

        $order = Order::where('stripe_payment_intent_id', $paymentIntentId)
            ->when($orderId, fn ($q) => $q->orWhere('id', $orderId))
            ->first();

        if ($order && $order->status !== Order::STATUS_PAID) {
            $order->update(['status' => Order::STATUS_PAID]);
            Log::info("Order #{$order->order_number} updated to paid via payment_intent.succeeded.");
        }
    }

    /**
     * Handle payment_intent.payment_failed event.
     *
     * @param  array<string, mixed>  $paymentIntent
     */
    protected function handlePaymentIntentFailed(array $paymentIntent): void
    {
        $paymentIntentId = $paymentIntent['id'] ?? null;
        $orderId = $paymentIntent['metadata']['order_id'] ?? null;

        $order = Order::where('stripe_payment_intent_id', $paymentIntentId)
            ->when($orderId, fn ($q) => $q->orWhere('id', $orderId))
            ->first();

        if ($order && $order->status === Order::STATUS_PENDING) {
            $order->update(['status' => Order::STATUS_CANCELLED]);
            Log::info("Order #{$order->order_number} marked as cancelled due to payment failure.");
        }
    }

    /**
     * Handle charge.refunded event.
     *
     * @param  array<string, mixed>  $charge
     */
    protected function handleChargeRefunded(array $charge): void
    {
        $paymentIntentId = $charge['payment_intent'] ?? null;

        if (! $paymentIntentId) {
            return;
        }

        $order = Order::where('stripe_payment_intent_id', $paymentIntentId)->first();

        if ($order instanceof Order) {
            DB::transaction(function () use ($order): void {
                $order->update(['status' => Order::STATUS_REFUNDED]);

                // Restore inventory
                foreach ($order->items as $item) {
                    if ($item->product_id) {
                        Product::where('id', $item->product_id)->increment('stock', $item->quantity);
                    }
                }
            });

            Log::info("Order #{$order->order_number} marked as refunded and stock restored.");
        }
    }
}
