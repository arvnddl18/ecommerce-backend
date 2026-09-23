<?php

namespace Tests\Feature;

use App\Jobs\ProcessStripeWebhookJob;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Stripe\Checkout\Session;
use Tests\TestCase;

class CouponAndDiscountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_can_apply_percentage_coupon_and_recalculate_cart_total(): void
    {
        $product = Product::factory()->create([
            'price' => 10000, // $100.00
            'stock' => 10,
            'is_active' => true,
        ]);

        $coupon = Coupon::create([
            'code' => 'SAVE20',
            'discount_percent' => 20,
            'is_active' => true,
        ]);

        $cartToken = 'guest_coupon_token_12345';
        $cartService = app(CartService::class);
        $cartService->addItem($cartToken, $product->id, 1);

        $response = $this->withHeader('X-Cart-Token', $cartToken)
            ->postJson(route('api.v1.cart.coupon.apply'), [
                'code' => 'SAVE20',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.subtotal', 10000)
            ->assertJsonPath('data.discount', 2000)
            ->assertJsonPath('data.total', 8000)
            ->assertJsonPath('data.coupon.code', 'SAVE20');
    }

    public function test_can_apply_fixed_amount_coupon(): void
    {
        $product = Product::factory()->create([
            'price' => 15000, // $150.00
            'stock' => 10,
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'FLAT15',
            'discount_amount' => 1500, // $15.00
            'is_active' => true,
        ]);

        $cartToken = 'guest_coupon_token_fixed';
        $cartService = app(CartService::class);
        $cartService->addItem($cartToken, $product->id, 1);

        $response = $this->withHeader('X-Cart-Token', $cartToken)
            ->postJson(route('api.v1.cart.coupon.apply'), [
                'code' => 'FLAT15',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.discount', 1500)
            ->assertJsonPath('data.total', 13500);
    }

    public function test_rejects_expired_or_subtotal_under_minimum_coupon(): void
    {
        $product = Product::factory()->create([
            'price' => 5000, // $50.00
            'stock' => 10,
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'EXPIRED10',
            'discount_percent' => 10,
            'expires_at' => now()->subDay(),
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'BIGSPENDER',
            'discount_percent' => 30,
            'min_order_amount' => 10000, // min $100.00
            'is_active' => true,
        ]);

        $cartToken = 'guest_coupon_token_invalid';
        $cartService = app(CartService::class);
        $cartService->addItem($cartToken, $product->id, 1);

        // Attempt expired coupon
        $resExpired = $this->withHeader('X-Cart-Token', $cartToken)
            ->postJson(route('api.v1.cart.coupon.apply'), ['code' => 'EXPIRED10']);
        $resExpired->assertStatus(422);

        // Attempt min amount not met
        $resMin = $this->withHeader('X-Cart-Token', $cartToken)
            ->postJson(route('api.v1.cart.coupon.apply'), ['code' => 'BIGSPENDER']);
        $resMin->assertStatus(422);
    }

    public function test_can_remove_coupon_from_cart(): void
    {
        $product = Product::factory()->create([
            'price' => 10000,
            'stock' => 10,
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'SAVE10',
            'discount_percent' => 10,
            'is_active' => true,
        ]);

        $cartToken = 'guest_coupon_token_remove';
        $cartService = app(CartService::class);
        $cartService->addItem($cartToken, $product->id, 1);
        $cartService->applyCoupon($cartToken, 'SAVE10');

        $response = $this->withHeader('X-Cart-Token', $cartToken)
            ->deleteJson(route('api.v1.cart.coupon.remove'));

        $response->assertStatus(200)
            ->assertJsonPath('data.discount', 0)
            ->assertJsonPath('data.total', 10000)
            ->assertJsonPath('data.coupon', null);
    }

    public function test_checkout_applies_discount_and_webhook_increments_usage(): void
    {
        $product = Product::factory()->create([
            'name' => 'Cashmere Overshirt',
            'price' => 20000, // $200.00
            'stock' => 5,
            'is_active' => true,
        ]);

        $coupon = Coupon::create([
            'code' => 'ATELIER25',
            'discount_percent' => 25,
            'is_active' => true,
            'uses_count' => 0,
        ]);

        $cartToken = 'guest_checkout_coupon_token_123';
        $cartService = app(CartService::class);
        $cartService->addItem($cartToken, $product->id, 1);
        $cartService->applyCoupon($cartToken, 'ATELIER25');

        // Mock Stripe
        $mockStripe = Mockery::mock(StripeService::class);
        $mockSession = new Session('cs_test_mock_discount_session');
        $mockSession->url = 'https://checkout.stripe.com/pay/mock_discount';

        $mockStripe->shouldReceive('createCheckoutSession')
            ->once()
            ->andReturn($mockSession);

        $this->app->instance(StripeService::class, $mockStripe);

        $response = $this->withHeader('X-Cart-Token', $cartToken)
            ->postJson(route('api.v1.checkout.session'), [
                'customer_email' => 'buyer@atelier.com',
                'cart_token' => $cartToken,
            ]);

        $response->assertStatus(200);

        // Order total should be $150.00 ($200 - 25% discount)
        $order = Order::where('customer_email', 'buyer@atelier.com')->firstOrFail();
        $this->assertEquals(15000, $order->total_amount);
        $this->assertEquals('ATELIER25', $order->metadata['coupon_code']);
        $this->assertEquals(5000, $order->metadata['discount_amount']);

        // Process webhook to verify uses_count increment
        $payload = [
            'id' => 'evt_coupon_fulfillment',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => $order->stripe_session_id,
                    'payment_intent' => 'pi_coupon_123',
                    'metadata' => [
                        'order_id' => (string) $order->id,
                        'order_number' => $order->order_number,
                    ],
                ],
            ],
        ];

        $job = new ProcessStripeWebhookJob($payload);
        $job->handle();

        $this->assertEquals(1, $coupon->fresh()->uses_count);
    }

    public function test_seller_can_create_and_view_shop_coupons(): void
    {
        $sellerUser = User::factory()->create(['role' => 'seller']);
        $sellerProfile = $sellerUser->sellerProfile()->create([
            'store_name' => 'Studio Noir',
            'slug' => 'studio-noir',
            'verification_status' => 'approved',
        ]);

        $response = $this->actingAs($sellerUser)
            ->postJson(route('api.v1.seller.coupons.store'), [
                'code' => 'STUDIO10',
                'discount_percent' => 10,
                'min_order_amount' => 5000,
                'max_uses' => 100,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('coupon.code', 'STUDIO10')
            ->assertJsonPath('coupon.discount_percent', 10)
            ->assertJsonPath('coupon.seller_id', $sellerProfile->id);

        $listResponse = $this->actingAs($sellerUser)
            ->getJson(route('api.v1.seller.coupons.index'));

        $listResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'STUDIO10');
    }
}
