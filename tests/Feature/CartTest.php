<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_empty_cart_returns_zero_totals(): void
    {
        $response = $this->withHeader('X-Cart-Token', 'test_cart_token_12345678')
            ->getJson(route('api.v1.cart.index'));

        $response->assertStatus(200)
            ->assertJsonPath('data.total_quantity', 0)
            ->assertJsonPath('data.subtotal', 0)
            ->assertJsonPath('data.items', []);
    }

    public function test_item_can_be_added_to_cart(): void
    {
        $product = Product::factory()->create([
            'price' => 15000, // $150.00
            'stock' => 10,
            'is_active' => true,
        ]);

        $response = $this->withHeader('X-Cart-Token', 'test_cart_token_12345678')
            ->postJson(route('api.v1.cart.store'), [
                'product_id' => $product->id,
                'quantity' => 2,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.total_quantity', 2)
            ->assertJsonPath('data.subtotal', 30000)
            ->assertJsonPath('data.items.0.product_id', $product->id);
    }

    public function test_cannot_add_more_than_available_stock(): void
    {
        $product = Product::factory()->create([
            'stock' => 3,
            'is_active' => true,
        ]);

        $response = $this->withHeader('X-Cart-Token', 'test_cart_token_12345678')
            ->postJson(route('api.v1.cart.store'), [
                'product_id' => $product->id,
                'quantity' => 5,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_item_quantity_can_be_updated(): void
    {
        $product = Product::factory()->create([
            'price' => 5000,
            'stock' => 10,
            'is_active' => true,
        ]);

        // Add 1 item
        $this->withHeader('X-Cart-Token', 'test_cart_token_12345678')
            ->postJson(route('api.v1.cart.store'), [
                'product_id' => $product->id,
                'quantity' => 1,
            ]);

        // Update to 3 items
        $response = $this->withHeader('X-Cart-Token', 'test_cart_token_12345678')
            ->putJson(route('api.v1.cart.update', $product->id), [
                'quantity' => 3,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.total_quantity', 3)
            ->assertJsonPath('data.subtotal', 15000);
    }

    public function test_item_can_be_removed_and_cart_cleared(): void
    {
        $product1 = Product::factory()->create(['stock' => 10]);
        $product2 = Product::factory()->create(['stock' => 10]);

        $token = 'test_cart_token_12345678';

        $this->withHeader('X-Cart-Token', $token)
            ->postJson(route('api.v1.cart.store'), ['product_id' => $product1->id, 'quantity' => 1]);
        $this->withHeader('X-Cart-Token', $token)
            ->postJson(route('api.v1.cart.store'), ['product_id' => $product2->id, 'quantity' => 2]);

        // Remove product 1
        $removeResponse = $this->withHeader('X-Cart-Token', $token)
            ->deleteJson(route('api.v1.cart.destroy', $product1->id));

        $removeResponse->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.product_id', $product2->id);

        // Clear remaining cart
        $clearResponse = $this->withHeader('X-Cart-Token', $token)
            ->deleteJson(route('api.v1.cart.clear'));

        $clearResponse->assertStatus(200)
            ->assertJsonPath('data.total_quantity', 0)
            ->assertJsonCount(0, 'data.items');
    }
}
