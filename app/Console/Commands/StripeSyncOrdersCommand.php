<?php

namespace App\Console\Commands;

use App\Jobs\ProcessStripeWebhookJob;
use App\Models\Order;
use App\Models\WebhookEvent;
use App\Services\StripeService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('stripe:sync-orders')]
#[Description('Synchronize pending orders with Stripe Checkout session payment status.')]
class StripeSyncOrdersCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(StripeService $stripeService): int
    {
        $this->info('Scanning pending orders for Stripe payment completion...');

        $pendingOrders = Order::where('status', Order::STATUS_PENDING)
            ->whereNotNull('stripe_session_id')
            ->with('items')
            ->get();

        if ($pendingOrders->isEmpty()) {
            $this->info('No pending orders with active Stripe checkout sessions found.');

            return self::SUCCESS;
        }

        $syncedCount = 0;
        $unpaidCount = 0;

        foreach ($pendingOrders as $order) {
            $session = $stripeService->retrieveCheckoutSession($order->stripe_session_id);

            if (! $session) {
                $this->warn("Order #{$order->order_number}: Session {$order->stripe_session_id} not found in Stripe.");

                continue;
            }

            if ($session->payment_status === 'paid' || $session->status === 'complete') {
                $this->info("Order #{$order->order_number}: Payment confirmed in Stripe ({$session->amount_total} {$session->currency}). Fulfilling order...");

                $eventId = 'evt_sync_'.$session->id.'_'.time();

                $eventData = [
                    'id' => $eventId,
                    'type' => 'checkout.session.completed',
                    'data' => [
                        'object' => [
                            'id' => $session->id,
                            'payment_intent' => $session->payment_intent,
                            'payment_status' => $session->payment_status,
                            'metadata' => [
                                'order_id' => $order->id,
                                'order_number' => $order->order_number,
                            ],
                        ],
                    ],
                ];

                WebhookEvent::create([
                    'stripe_event_id' => $eventId,
                    'type' => 'checkout.session.completed',
                    'payload' => $eventData,
                    'processed_at' => now(),
                ]);

                $job = new ProcessStripeWebhookJob($eventData);
                $job->handle();

                $syncedCount++;
            } else {
                $this->line("Order #{$order->order_number}: Stripe session is '{$session->status}', payment_status is '{$session->payment_status}'. (Awaiting payment)");
                $unpaidCount++;
            }
        }

        $this->newLine();
        $this->info("Stripe synchronization complete! {$syncedCount} order(s) fulfilled, {$unpaidCount} unpaid/open.");

        return self::SUCCESS;
    }
}
