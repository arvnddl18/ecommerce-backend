<?php

namespace App\Jobs;

use App\Mail\OrderConfirmationMail;
use App\Mail\SellerOrderNotificationMail;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SellerProfile;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Stripe;
use Stripe\Transfer;

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
            'account.updated' => $this->handleAccountUpdated($dataObject),
            'transfer.created' => $this->handleTransferCreated($dataObject),
            'payout.paid' => $this->handlePayoutPaid($dataObject),
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

        $this->fulfillOrder($order, $paymentIntentId);
    }

    /**
     * Atomically fulfill a paid order, deduct stock, disburse payouts, and dispatch buyer confirmation email.
     */
    protected function fulfillOrder(Order $order, ?string $paymentIntentId = null): void
    {
        if ($order->status === Order::STATUS_PAID) {
            Log::info("Order #{$order->order_number} already marked as paid.");

            return;
        }

        DB::transaction(function () use ($order, $paymentIntentId): void {
            $order->update([
                'status' => Order::STATUS_PAID,
                'stripe_payment_intent_id' => $paymentIntentId ?? $order->stripe_payment_intent_id,
            ]);

            // Deduct inventory for purchased items (both variants and parent products)
            foreach ($order->items as $item) {
                if ($item->variant_id) {
                    ProductVariant::where('id', $item->variant_id)
                        ->where('stock_quantity', '>=', $item->quantity)
                        ->decrement('stock_quantity', $item->quantity);
                }

                if ($item->product_id) {
                    Product::where('id', $item->product_id)
                        ->where('stock', '>=', $item->quantity)
                        ->decrement('stock', $item->quantity);
                }
            }

            // Increment coupon usage count if coupon was applied
            $couponCode = $order->metadata['coupon_code'] ?? null;
            if ($couponCode) {
                Coupon::where('code', $couponCode)->increment('uses_count');
            }
        });

        // Resolve buyer email from order record or linked user account
        $buyerEmail = $order->customer_email ?: $order->user?->email;

        // Automatically dispatch confirmation email to buyer via Resend
        if ($buyerEmail) {
            try {
                Mail::to($buyerEmail)->queue(new OrderConfirmationMail($order));
                Log::info("Order confirmation email successfully queued to {$buyerEmail} for order #{$order->order_number}");
            } catch (\Throwable $e) {
                Log::error("Failed to queue OrderConfirmationMail for order #{$order->order_number}: {$e->getMessage()}");
            }
        } else {
            Log::warning("No recipient email found for order #{$order->order_number}; skipping buyer confirmation mail.");
        }

        // Disburse seller payouts via Stripe Connect and dispatch seller emails
        $this->processSellerFulfillmentAndTransfers($order);

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

        if ($order instanceof Order) {
            $this->fulfillOrder($order, $paymentIntentId);
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

                // Restore inventory for both variant and base product
                foreach ($order->items as $item) {
                    if ($item->variant_id) {
                        ProductVariant::where('id', $item->variant_id)
                            ->increment('stock_quantity', $item->quantity);
                    }

                    if ($item->product_id) {
                        Product::where('id', $item->product_id)->increment('stock', $item->quantity);
                    }
                }
            });

            Log::info("Order #{$order->order_number} marked as refunded and stock restored.");
        }
    }

    /**
     * Handle Stripe Connect account.updated event.
     *
     * @param  array<string, mixed>  $account
     */
    protected function handleAccountUpdated(array $account): void
    {
        $accountId = $account['id'] ?? null;
        if (! $accountId) {
            return;
        }

        $seller = SellerProfile::where('stripe_account_id', $accountId)->first();
        if ($seller) {
            $payoutsEnabled = $account['payouts_enabled'] ?? false;
            $detailsSubmitted = $account['details_submitted'] ?? false;

            if ($payoutsEnabled && $detailsSubmitted) {
                $seller->update(['verification_status' => 'approved']);
                Log::info("Seller #{$seller->id} verified and payouts enabled via account.updated.");
            }
        }
    }

    /**
     * Handle transfer.created event for seller payouts.
     *
     * @param  array<string, mixed>  $transfer
     */
    protected function handleTransferCreated(array $transfer): void
    {
        $transferId = $transfer['id'] ?? null;
        $transferGroup = $transfer['transfer_group'] ?? null;
        $destination = $transfer['destination'] ?? null;
        $amount = $transfer['amount'] ?? 0;

        Log::info("Stripe Connect transfer created: {$transferId} for group: {$transferGroup} to: {$destination} (Amount: {$amount})");
    }

    /**
     * Handle payout.paid event.
     *
     * @param  array<string, mixed>  $payout
     */
    protected function handlePayoutPaid(array $payout): void
    {
        $payoutId = $payout['id'] ?? null;
        $amount = $payout['amount'] ?? 0;

        Log::info("Stripe Connect payout paid: {$payoutId} (Amount: {$amount})");
    }

    /**
     * Disburse Stripe Connect transfers to sellers and queue dispatch alerts.
     */
    protected function processSellerFulfillmentAndTransfers(Order $order): void
    {
        $itemsBySeller = $order->items->groupBy('seller_id');
        $stripeSecret = config('services.stripe.secret');

        foreach ($itemsBySeller as $sellerId => $sellerItems) {
            if (! $sellerId) {
                continue;
            }

            /** @var SellerProfile|null $seller */
            $seller = SellerProfile::with('user')->find($sellerId);
            if (! $seller) {
                continue;
            }

            $grossRevenue = (int) $sellerItems->sum('total_price');
            $platformFee = (int) round($grossRevenue * 0.10); // 10% commission
            $netPayout = $grossRevenue - $platformFee;

            // Update seller total sales tracking
            $seller->increment('total_sales', $netPayout);

            // Trigger Stripe Connect transfer if seller has a connected account
            if ($seller->stripe_account_id && $netPayout > 0 && $stripeSecret) {
                try {
                    Stripe::setApiKey($stripeSecret);
                    Transfer::create([
                        'amount' => $netPayout,
                        'currency' => strtolower($order->currency ?: 'usd'),
                        'destination' => $seller->stripe_account_id,
                        'transfer_group' => "ORDER_{$order->order_number}",
                        'metadata' => [
                            'order_id' => (string) $order->id,
                            'order_number' => (string) $order->order_number,
                            'seller_id' => (string) $seller->id,
                            'gross_revenue' => (string) $grossRevenue,
                            'platform_commission' => (string) $platformFee,
                        ],
                    ]);
                    Log::info("Disbursed Stripe Connect transfer of {$netPayout} to seller #{$seller->id} for order #{$order->order_number}");
                } catch (\Throwable $e) {
                    Log::error("Failed to disburse Stripe Connect transfer to seller #{$seller->id}: {$e->getMessage()}");
                }
            }

            // Queue notification email to seller
            $sellerUser = $seller->user;
            if ($sellerUser instanceof User && $sellerUser->email) {
                Mail::to($sellerUser->email)->queue(new SellerOrderNotificationMail($order, $seller, $sellerItems, $netPayout));
            }
        }
    }
}
