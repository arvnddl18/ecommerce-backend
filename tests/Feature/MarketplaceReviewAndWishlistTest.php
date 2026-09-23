<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceReviewAndWishlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_purchaser_can_submit_product_review(): void
    {
        $user = User::factory()->create(['role' => 'buyer']);
        $token = $user->createToken('buyer_token')->plainTextToken;
        $product = Product::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'paid',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => $product->price,
            'quantity' => 1,
            'total_price' => $product->price,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson(route('api.v1.reviews.store', $product), [
                'rating' => 5,
                'comment' => 'Incredible fabric quality, very premium feel.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('review.rating', 5);

        $this->assertDatabaseHas('reviews', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 5,
        ]);
    }

    public function test_non_purchaser_cannot_submit_review(): void
    {
        $user = User::factory()->create(['role' => 'buyer']);
        $token = $user->createToken('buyer_token')->plainTextToken;
        $product = Product::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson(route('api.v1.reviews.store', $product), [
                'rating' => 1,
                'comment' => 'Fake review without purchasing.',
            ]);

        $response->assertStatus(403);
    }

    public function test_user_can_toggle_wishlist(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('user_token')->plainTextToken;
        $product = Product::factory()->create();

        // 1. Add to wishlist
        $addResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson(route('api.v1.wishlist.toggle', $product));

        $addResponse->assertStatus(200)
            ->assertJsonPath('is_wishlisted', true);

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        // 2. Remove from wishlist
        $removeResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson(route('api.v1.wishlist.toggle', $product));

        $removeResponse->assertStatus(200)
            ->assertJsonPath('is_wishlisted', false);

        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_user_can_save_and_manage_addresses(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('user_token')->plainTextToken;

        $storeResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson(route('api.v1.addresses.store'), [
                'line1' => '742 Evergreen Terrace',
                'city' => 'Springfield',
                'province' => 'OR',
                'postal_code' => '97477',
                'is_default' => true,
            ]);

        $storeResponse->assertStatus(201)
            ->assertJsonPath('address.city', 'Springfield');

        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'city' => 'Springfield',
        ]);

        $address = Address::where('user_id', $user->id)->firstOrFail();

        $deleteResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson(route('api.v1.addresses.destroy', $address));

        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('addresses', ['id' => $address->id]);
    }
}
