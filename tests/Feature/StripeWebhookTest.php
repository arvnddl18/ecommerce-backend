<?php

namespace Tests\Feature;

use App\Jobs\ProcessStripeWebhookJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\WebhookEvent;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_returns_400_when_signature_is_invalid(): void
    {
        $mockStripe = Mockery::mock(StripeService::class);
        $mockStripe->shouldReceive('constructWebhookEvent')
            ->once()
            ->andThrow(new SignatureVerificationException('Signature mismatch'));

        $this->app->instance(StripeService::class, $mockStripe);

        $response = $this->call(
            'POST',
            route('api.v1.webhooks.stripe'),
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => 'invalid_signature_header'],
            json_encode(['type' => 'test'])
        );

        $response->assertStatus(400)
            ->assertJsonPath('error', 'Invalid signature');
    }

    public function test_webhook_accepts_valid_signature_and_dispatches_job(): void
    {
        Queue::fake();

        $eventPayload = [
            'id' => 'evt_test_1234567890',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_session_abc',
                    'payment_intent' => 'pi_test_12345',
                    'metadata' => [
                        'order_id' => '1',
                        'order_number' => 'ORD-12345678',
                    ],
                ],
            ],
        ];

        $stripeEvent = Event::constructFrom($eventPayload);

        $mockStripe = Mockery::mock(StripeService::class);
        $mockStripe->shouldReceive('constructWebhookEvent')
            ->once()
            ->andReturn($stripeEvent);

        $this->app->instance(StripeService::class, $mockStripe);

        $response = $this->call(
            'POST',
            route('api.v1.webhooks.stripe'),
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => 'valid_mock_signature'],
            json_encode($eventPayload)
        );

        $response->assertStatus(200)
            ->assertJsonPath('received', true)
            ->assertJsonPath('event_id', 'evt_test_1234567890');

        $this->assertDatabaseHas('webhook_events', [
            'stripe_event_id' => 'evt_test_1234567890',
            'type' => 'checkout.session.completed',
        ]);

        Queue::assertPushed(ProcessStripeWebhookJob::class);
    }

    public function test_duplicate_webhook_event_is_ignored_for_idempotency(): void
    {
        Queue::fake();

        WebhookEvent::create([
            'stripe_event_id' => 'evt_already_processed_999',
            'type' => 'checkout.session.completed',
            'payload' => ['sample' => true],
            'processed_at' => now(),
        ]);

        $eventPayload = [
            'id' => 'evt_already_processed_999',
            'type' => 'checkout.session.completed',
            'data' => ['object' => []],
        ];

        $stripeEvent = Event::constructFrom($eventPayload);

        $mockStripe = Mockery::mock(StripeService::class);
        $mockStripe->shouldReceive('constructWebhookEvent')
            ->once()
            ->andReturn($stripeEvent);

        $this->app->instance(StripeService::class, $mockStripe);

        $response = $this->call(
            'POST',
            route('api.v1.webhooks.stripe'),
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => 'valid_mock_sig'],
            json_encode($eventPayload)
        );

        $response->assertStatus(200)
            ->assertJsonPath('status', 'already_processed');

        Queue::assertNothingPushed();
    }

    public function test_job_fulfills_order_and_decrements_inventory(): void
    {
        $product = Product::factory()->create([
            'stock' => 20,
        ]);

        $order = Order::factory()->create([
            'status' => Order::STATUS_PENDING,
            'stripe_session_id' => 'cs_fulfillment_session_123',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => $product->price,
            'quantity' => 4,
            'total_price' => $product->price * 4,
        ]);

        $eventPayload = [
            'id' => 'evt_fulfillment_test',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_fulfillment_session_123',
                    'payment_intent' => 'pi_success_999',
                    'metadata' => [
                        'order_id' => (string) $order->id,
                        'order_number' => $order->order_number,
                    ],
                ],
            ],
        ];

        $job = new ProcessStripeWebhookJob($eventPayload);
        $job->handle();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_PAID,
            'stripe_payment_intent_id' => 'pi_success_999',
        ]);

        // Stock was 20, purchased 4 -> should be 16
        $this->assertEquals(16, $product->fresh()->stock);
    }
}
