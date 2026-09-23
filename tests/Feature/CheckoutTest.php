<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Services\CartService;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Checkout\Session;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_checkout_with_empty_cart(): void
    {
        $response = $this->postJson(route('api.v1.checkout.session'), [
            'customer_email' => 'customer@example.com',
            'cart_token' => 'empty_cart_token_12345678',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cart']);
    }

    public function test_checkout_creates_order_and_stripe_session(): void
    {
        $product = Product::factory()->create([
            'name' => 'AeroSound Headphones',
            'price' => 25000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $cartToken = 'checkout_token_12345678';
        app(CartService::class)->addItem($cartToken, $product->id, 2);

        // Mock StripeService so we do not make real external HTTP requests during tests
        $mockStripe = Mockery::mock(StripeService::class);
        $mockSession = new Session('cs_test_mock_session_123');
        $mockSession->url = 'https://checkout.stripe.com/c/pay/cs_test_mock_session_123';

        $mockStripe->shouldReceive('createCheckoutSession')
            ->once()
            ->andReturn($mockSession);

        $this->app->instance(StripeService::class, $mockStripe);

        $response = $this->postJson(route('api.v1.checkout.session'), [
            'customer_email' => 'buyer@example.com',
            'customer_name' => 'Buyer Name',
            'cart_token' => $cartToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'order_number',
                'session_id',
                'checkout_url',
            ])
            ->assertJsonPath('session_id', 'cs_test_mock_session_123')
            ->assertJsonPath('checkout_url', 'https://checkout.stripe.com/c/pay/cs_test_mock_session_123');

        $this->assertDatabaseHas('orders', [
            'customer_email' => 'buyer@example.com',
            'total_amount' => 50000,
            'status' => Order::STATUS_PENDING,
            'stripe_session_id' => 'cs_test_mock_session_123',
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_name' => 'AeroSound Headphones',
            'quantity' => 2,
            'total_price' => 50000,
        ]);

        // Verify cart was cleared
        $cartAfter = app(CartService::class)->getCart($cartToken);
        $this->assertEquals(0, $cartAfter['total_quantity']);
    }

    public function test_can_retrieve_order_status_by_order_number(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'ORD-TEST123456',
            'total_amount' => 29900,
            'status' => Order::STATUS_PAID,
            'customer_email' => 'alice@example.com',
        ]);

        $response = $this->getJson(route('api.v1.checkout.orders.status', $order->order_number));

        $response->assertStatus(200)
            ->assertJsonPath('data.order_number', 'ORD-TEST123456')
            ->assertJsonPath('data.status', 'paid');
    }

    public function test_get_order_status_auto_fulfills_when_stripe_session_is_paid(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'ORD-AUTO-FULFILL-123',
            'status' => Order::STATUS_PENDING,
            'stripe_session_id' => 'cs_test_auto_paid_session',
        ]);

        $mockStripe = Mockery::mock(StripeService::class);
        $mockSession = Session::constructFrom([
            'id' => 'cs_test_auto_paid_session',
            'payment_status' => 'paid',
            'status' => 'complete',
            'amount_total' => 10000,
            'currency' => 'php',
            'payment_intent' => 'pi_test_auto_123',
        ]);

        $mockStripe->shouldReceive('retrieveCheckoutSession')
            ->with('cs_test_auto_paid_session')
            ->once()
            ->andReturn($mockSession);

        $this->app->instance(StripeService::class, $mockStripe);

        $response = $this->getJson(route('api.v1.checkout.orders.status', $order->stripe_session_id));

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'paid');

        $order->refresh();
        $this->assertEquals(Order::STATUS_PAID, $order->status);
    }
}
