<?php

namespace App\Services;

use App\Models\Order;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeService
{
    protected ?StripeClient $client = null;

    /**
     * Get or initialize the Stripe client.
     */
    public function getClient(): StripeClient
    {
        if ($this->client === null) {
            $secretKey = config('services.stripe.secret') ?? 'sk_test_placeholder';
            $this->client = new StripeClient($secretKey);
        }

        return $this->client;
    }

    /**
     * Create a Stripe Checkout Session for an order.
     */
    public function createCheckoutSession(
        Order $order,
        string $successUrl,
        string $cancelUrl
    ): Session {
        $lineItems = [];

        foreach ($order->items as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => $order->currency ?? 'usd',
                    'product_data' => [
                        'name' => $item->product_name,
                    ],
                    'unit_amount' => $item->unit_price, // In cents
                ],
                'quantity' => $item->quantity,
            ];
        }

        return $this->getClient()->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'customer_email' => $order->customer_email,
            'client_reference_id' => (string) $order->id,
            'payment_intent_data' => [
                'transfer_group' => $order->order_number,
            ],
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => $order->order_number,
            ],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);
    }

    /**
     * Verify and construct incoming Stripe webhook event.
     *
     * @throws \UnexpectedValueException
     * @throws SignatureVerificationException
     */
    public function constructWebhookEvent(string $payload, ?string $sigHeader): Event
    {
        $secret = config('services.stripe.webhook_secret') ?? 'whsec_placeholder';

        return Webhook::constructEvent(
            $payload,
            $sigHeader ?? '',
            $secret
        );
    }
}
