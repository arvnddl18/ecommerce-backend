<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Checkout\Session;
use Tests\TestCase;

class StripeSyncOrdersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_stripe_sync_orders_fulfills_paid_sessions(): void
    {
        $sellerUser = User::factory()->create(['role' => 'seller']);
        $seller = SellerProfile::factory()->create(['user_id' => $sellerUser->id]);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'price' => 5000,
            'stock' => 10,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-SYNC-TEST-001',
            'status' => Order::STATUS_PENDING,
            'total_amount' => 5000,
            'currency' => 'php',
            'customer_email' => 'buyer@test.com',
            'stripe_session_id' => 'cs_test_mock_paid_session_123',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'seller_id' => $seller->id,
            'product_name' => $product->name,
            'unit_price' => 5000,
            'quantity' => 1,
            'total_price' => 5000,
            'fulfillment_status' => 'pending',
        ]);

        $mockSession = Session::constructFrom([
            'id' => 'cs_test_mock_paid_session_123',
            'payment_status' => 'paid',
            'status' => 'complete',
            'amount_total' => 5000,
            'currency' => 'php',
            'payment_intent' => 'pi_mock_123',
        ]);

        $mockStripeService = Mockery::mock(StripeService::class);
        $mockStripeService->shouldReceive('retrieveCheckoutSession')
            ->with('cs_test_mock_paid_session_123')
            ->once()
            ->andReturn($mockSession);

        $this->app->instance(StripeService::class, $mockStripeService);

        $this->artisan('stripe:sync-orders')
            ->assertExitCode(0);

        $order->refresh();
        $this->assertEquals(Order::STATUS_PAID, $order->status);
        $this->assertEquals('pi_mock_123', $order->stripe_payment_intent_id);

        // Inventory should be deducted
        $product->refresh();
        $this->assertEquals(9, $product->stock);
    }

    public function test_stripe_sync_orders_skips_unpaid_sessions(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-SYNC-TEST-002',
            'status' => Order::STATUS_PENDING,
            'total_amount' => 4500,
            'currency' => 'php',
            'customer_email' => 'buyer2@test.com',
            'stripe_session_id' => 'cs_test_mock_unpaid_session_456',
        ]);

        $mockSession = Session::constructFrom([
            'id' => 'cs_test_mock_unpaid_session_456',
            'payment_status' => 'unpaid',
            'status' => 'open',
            'amount_total' => 4500,
            'currency' => 'php',
            'payment_intent' => null,
        ]);

        $mockStripeService = Mockery::mock(StripeService::class);
        $mockStripeService->shouldReceive('retrieveCheckoutSession')
            ->with('cs_test_mock_unpaid_session_456')
            ->once()
            ->andReturn($mockSession);

        $this->app->instance(StripeService::class, $mockStripeService);

        $this->artisan('stripe:sync-orders')
            ->assertExitCode(0);

        $order->refresh();
        $this->assertEquals(Order::STATUS_PENDING, $order->status);
    }

    public function test_seller_dashboard_excludes_pending_unpaid_orders(): void
    {
        $sellerUser = User::factory()->create(['role' => 'seller']);
        $seller = SellerProfile::factory()->create(['user_id' => $sellerUser->id]);
        $token = $sellerUser->createToken('seller_token')->plainTextToken;

        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'price' => 4800,
        ]);

        // Unpaid pending order
        $pendingOrder = Order::create([
            'order_number' => 'ORD-PENDING-TEST-999',
            'status' => Order::STATUS_PENDING,
            'total_amount' => 4800,
            'currency' => 'php',
            'customer_email' => 'abandoned@test.com',
        ]);

        OrderItem::create([
            'order_id' => $pendingOrder->id,
            'product_id' => $product->id,
            'seller_id' => $seller->id,
            'product_name' => $product->name,
            'unit_price' => 4800,
            'quantity' => 1,
            'total_price' => 4800,
            'fulfillment_status' => 'shipped', // Even if erroneously marked shipped
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson(route('api.v1.seller.dashboard'));

        $response->assertStatus(200)
            ->assertJsonPath('stats.total_revenue', 0)
            ->assertJsonPath('stats.formatted_revenue', '₱0.00')
            ->assertJsonPath('stats.items_sold', 0);
    }
}
