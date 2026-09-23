<?php

namespace Tests\Feature;

use App\Jobs\ProcessStripeWebhookJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\CartService;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Stripe\Checkout\Session;
use Tests\TestCase;

class MarketplaceVariantCartAndCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_can_add_apparel_variant_to_cart_with_price_override(): void
    {
        $seller = SellerProfile::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'price' => 12000, // $120.00 base
            'stock' => 50,
            'is_active' => true,
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'size' => 'L',
            'color' => 'Onyx Black',
            'sku' => 'TSHIRT-BLK-L',
            'stock_quantity' => 15,
            'price_override' => 13500, // $135.00 variant price
        ]);

        $cartToken = 'cart_token_variant_test_123';

        $response = $this->withHeader('X-Cart-Token', $cartToken)
            ->postJson(route('api.v1.cart.store'), [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 2,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.total_quantity', 2)
            ->assertJsonPath('data.subtotal', 27000) // 13500 * 2
            ->assertJsonPath('data.items.0.product_id', $product->id)
            ->assertJsonPath('data.items.0.variant_id', $variant->id)
            ->assertJsonPath('data.items.0.size', 'L')
            ->assertJsonPath('data.items.0.color', 'Onyx Black')
            ->assertJsonPath('data.items.0.price', 13500);
    }

    public function test_cannot_add_more_than_variant_stock_quantity(): void
    {
        $product = Product::factory()->create(['stock' => 100]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 3,
        ]);

        $cartToken = 'cart_token_variant_limit_123';

        $response = $this->withHeader('X-Cart-Token', $cartToken)
            ->postJson(route('api.v1.cart.store'), [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 4,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_multi_vendor_checkout_stores_variant_and_seller_denormalization(): void
    {
        $sellerA = SellerProfile::factory()->create(['store_name' => 'Atelier Alpha']);
        $sellerB = SellerProfile::factory()->create(['store_name' => 'Maison Beta']);

        $productA = Product::factory()->create(['seller_id' => $sellerA->id, 'price' => 10000, 'stock' => 20]);
        $variantA = ProductVariant::factory()->create([
            'product_id' => $productA->id,
            'size' => 'M',
            'color' => 'Navy',
            'stock_quantity' => 10,
            'price_override' => null,
        ]);

        $productB = Product::factory()->create(['seller_id' => $sellerB->id, 'price' => 15000, 'stock' => 20]);
        $variantB = ProductVariant::factory()->create([
            'product_id' => $productB->id,
            'size' => 'XL',
            'color' => 'Olive',
            'stock_quantity' => 8,
            'price_override' => 16000,
        ]);

        $cartToken = 'multi_seller_cart_123';
        $cartService = app(CartService::class);
        $cartService->addItem($cartToken, $productA->id, 1, $variantA->id);
        $cartService->addItem($cartToken, $productB->id, 2, $variantB->id);

        $mockStripe = Mockery::mock(StripeService::class);
        $mockSession = new Session('cs_test_multi_seller_999');
        $mockSession->url = 'https://checkout.stripe.com/c/pay/cs_test_multi_seller_999';

        $mockStripe->shouldReceive('createCheckoutSession')
            ->once()
            ->andReturn($mockSession);

        $this->app->instance(StripeService::class, $mockStripe);

        $buyer = User::factory()->create(['role' => 'buyer', 'age_verified' => true]);

        $response = $this->actingAs($buyer, 'sanctum')
            ->postJson(route('api.v1.checkout.session'), [
                'customer_email' => 'buyer@fashion.com',
                'customer_name' => 'Haute Buyer',
                'cart_token' => $cartToken,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['order_number', 'session_id', 'checkout_url']);

        $order = Order::where('stripe_session_id', 'cs_test_multi_seller_999')->firstOrFail();

        $this->assertEquals(42000, $order->total_amount); // 10000 + (16000 * 2) = 42000
        $this->assertCount(2, $order->items);

        $itemA = $order->items->where('product_id', $productA->id)->first();
        $this->assertNotNull($itemA);
        $this->assertEquals($sellerA->id, $itemA->seller_id);
        $this->assertEquals($variantA->id, $itemA->variant_id);
        $this->assertEquals('M', $itemA->variant_details['size']);

        $itemB = $order->items->where('product_id', $productB->id)->first();
        $this->assertNotNull($itemB);
        $this->assertEquals($sellerB->id, $itemB->seller_id);
        $this->assertEquals($variantB->id, $itemB->variant_id);
        $this->assertEquals(2, $itemB->quantity);
        $this->assertEquals(32000, $itemB->total_price);
    }

    public function test_webhook_decrements_both_variant_and_product_stock(): void
    {
        $product = Product::factory()->create(['stock' => 30]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 12,
        ]);

        $order = Order::factory()->create([
            'status' => Order::STATUS_PENDING,
            'stripe_session_id' => 'cs_variant_fulfill_test',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'product_name' => $product->name.' (M / Slate)',
            'unit_price' => $product->price,
            'quantity' => 3,
            'total_price' => $product->price * 3,
        ]);

        $eventPayload = [
            'id' => 'evt_variant_fulfill_001',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_variant_fulfill_test',
                    'payment_intent' => 'pi_variant_success_777',
                    'metadata' => [
                        'order_id' => (string) $order->id,
                        'order_number' => $order->order_number,
                    ],
                ],
            ],
        ];

        $job = new ProcessStripeWebhookJob($eventPayload);
        $job->handle();

        $this->assertEquals(27, $product->fresh()->stock); // 30 - 3
        $this->assertEquals(9, $variant->fresh()->stock_quantity); // 12 - 3
        $this->assertEquals(Order::STATUS_PAID, $order->fresh()->status);
    }

    public function test_webhook_restores_variant_stock_on_refund(): void
    {
        $product = Product::factory()->create(['stock' => 15]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 5,
        ]);

        $order = Order::factory()->create([
            'status' => Order::STATUS_PAID,
            'stripe_payment_intent_id' => 'pi_refund_test_888',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'product_name' => $product->name,
            'unit_price' => $product->price,
            'quantity' => 2,
            'total_price' => $product->price * 2,
        ]);

        $eventPayload = [
            'id' => 'evt_refund_001',
            'type' => 'charge.refunded',
            'data' => [
                'object' => [
                    'payment_intent' => 'pi_refund_test_888',
                ],
            ],
        ];

        $job = new ProcessStripeWebhookJob($eventPayload);
        $job->handle();

        $this->assertEquals(17, $product->fresh()->stock); // 15 + 2
        $this->assertEquals(7, $variant->fresh()->stock_quantity); // 5 + 2
        $this->assertEquals(Order::STATUS_REFUNDED, $order->fresh()->status);
    }

    public function test_stripe_connect_account_updated_enables_seller_payout_approval(): void
    {
        $seller = SellerProfile::factory()->create([
            'stripe_account_id' => 'acct_connect_seller_xyz',
            'verification_status' => 'pending',
        ]);

        $eventPayload = [
            'id' => 'evt_account_updated_001',
            'type' => 'account.updated',
            'data' => [
                'object' => [
                    'id' => 'acct_connect_seller_xyz',
                    'payouts_enabled' => true,
                    'details_submitted' => true,
                ],
            ],
        ];

        $job = new ProcessStripeWebhookJob($eventPayload);
        $job->handle();

        $this->assertEquals('approved', $seller->fresh()->verification_status);
    }
}
