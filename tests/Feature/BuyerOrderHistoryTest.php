<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BuyerOrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_orders(): void
    {
        $response = $this->getJson('/api/v1/orders');
        $response->assertStatus(401);
    }

    public function test_buyer_can_view_their_own_order_history(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $otherBuyer = User::factory()->create(['role' => 'buyer']);

        $category = Category::create(['name' => 'Tops', 'slug' => 'tops']);
        $product = Product::factory()->create(['category_id' => $category->id]);

        $order1 = Order::create([
            'user_id' => $buyer->id,
            'order_number' => 'ORD-BUYER-001',
            'status' => Order::STATUS_PAID,
            'total_amount' => 15000,
            'currency' => 'usd',
            'customer_email' => $buyer->email,
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => 15000,
            'quantity' => 1,
            'total_price' => 15000,
            'fulfillment_status' => 'pending',
        ]);

        // Another buyer's order
        Order::create([
            'user_id' => $otherBuyer->id,
            'order_number' => 'ORD-OTHER-002',
            'status' => Order::STATUS_PAID,
            'total_amount' => 8000,
            'currency' => 'usd',
            'customer_email' => $otherBuyer->email,
        ]);

        Sanctum::actingAs($buyer);

        $response = $this->getJson('/api/v1/orders');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.order_number', 'ORD-BUYER-001');
    }

    public function test_buyer_can_view_single_order_details(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $category = Category::create(['name' => 'Outerwear', 'slug' => 'outerwear']);
        $product = Product::factory()->create(['category_id' => $category->id]);

        $order = Order::create([
            'user_id' => $buyer->id,
            'order_number' => 'ORD-BUYER-DETAILS',
            'status' => Order::STATUS_PAID,
            'total_amount' => 24000,
            'currency' => 'usd',
            'customer_email' => $buyer->email,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => 24000,
            'quantity' => 1,
            'total_price' => 24000,
            'fulfillment_status' => 'pending',
        ]);

        Sanctum::actingAs($buyer);

        $response = $this->getJson("/api/v1/orders/{$order->id}");
        $response->assertStatus(200)
            ->assertJsonPath('order.order_number', 'ORD-BUYER-DETAILS')
            ->assertJsonCount(1, 'order.items');
    }

    public function test_buyer_cannot_view_another_buyers_order(): void
    {
        $buyer1 = User::factory()->create(['role' => 'buyer']);
        $buyer2 = User::factory()->create(['role' => 'buyer']);

        $order = Order::create([
            'user_id' => $buyer1->id,
            'order_number' => 'ORD-PRIVATE-001',
            'status' => Order::STATUS_PAID,
            'total_amount' => 12000,
            'currency' => 'usd',
            'customer_email' => $buyer1->email,
        ]);

        Sanctum::actingAs($buyer2);

        $response = $this->getJson("/api/v1/orders/{$order->id}");
        $response->assertStatus(403);
    }
}
