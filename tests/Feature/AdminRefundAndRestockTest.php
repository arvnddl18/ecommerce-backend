<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRefundAndRestockTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_all_marketplace_orders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);

        Order::factory()->count(3)->create([
            'user_id' => $buyer->id,
            'status' => Order::STATUS_PAID,
        ]);

        $response = $this->actingAs($admin)->getJson(route('api.v1.admin.orders.index'));

        $response->assertStatus(200)
            ->assertJsonPath('total', 3);
    }

    public function test_admin_can_refund_order_and_atomically_restock_inventory(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);

        $product = Product::factory()->create([
            'name' => 'Pleated Wool Trousers',
            'stock' => 10,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'M',
            'color' => 'Navy',
            'sku' => 'PWT-M-NAVY',
            'stock_quantity' => 4,
        ]);

        $order = Order::factory()->create([
            'user_id' => $buyer->id,
            'status' => Order::STATUS_PAID,
            'total_amount' => 32000,
            'stripe_payment_intent_id' => 'pi_test_refund_intent_123',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'product_name' => 'Pleated Wool Trousers',
            'unit_price' => 16000,
            'quantity' => 2,
            'total_price' => 32000,
        ]);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.orders.refund', $order->id));

        $response->assertStatus(200)
            ->assertJsonPath('order.status', Order::STATUS_REFUNDED);

        $this->assertEquals(Order::STATUS_REFUNDED, $order->fresh()->status);

        // Product stock restored (+2 from 10 = 12)
        $this->assertEquals(12, $product->fresh()->stock);

        // Variant stock restored (+2 from 4 = 6)
        $this->assertEquals(6, $variant->fresh()->stock_quantity);
    }

    public function test_non_admin_cannot_refund_orders(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $order = Order::factory()->create(['status' => Order::STATUS_PAID]);

        $response = $this->actingAs($buyer)
            ->postJson(route('api.v1.admin.orders.refund', $order->id));

        $response->assertStatus(403);
    }
}
