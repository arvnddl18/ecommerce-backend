<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceSellerTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_registration_creates_pending_seller_profile(): void
    {
        $response = $this->postJson(route('api.v1.auth.register'), [
            'name' => 'Komorebi Studio',
            'email' => 'komorebi@example.com',
            'password' => 'secret1234',
            'role' => 'seller',
            'store_name' => 'Komorebi Tokyo',
            'age_verified' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('user.role', 'seller')
            ->assertJsonPath('user.age_verified', true)
            ->assertJsonPath('user.seller_profile.store_name', 'Komorebi Tokyo')
            ->assertJsonPath('user.seller_profile.verification_status', 'pending');

        $this->assertDatabaseHas('seller_profiles', [
            'store_name' => 'Komorebi Tokyo',
            'verification_status' => 'pending',
        ]);
    }

    public function test_seller_can_access_dashboard_metrics(): void
    {
        $sellerUser = User::factory()->create(['role' => 'seller']);
        $seller = SellerProfile::factory()->create(['user_id' => $sellerUser->id]);
        $token = $sellerUser->createToken('seller_token')->plainTextToken;

        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'price' => 10000,
        ]);

        $order = Order::factory()->create(['status' => 'paid']);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'seller_id' => $seller->id,
            'product_name' => $product->name,
            'unit_price' => 10000,
            'quantity' => 2,
            'total_price' => 20000,
            'fulfillment_status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson(route('api.v1.seller.dashboard'));

        $response->assertStatus(200)
            ->assertJsonPath('stats.total_revenue', 20000)
            ->assertJsonPath('stats.pending_fulfillment', 1);
    }

    public function test_seller_can_create_product_with_variants_and_images(): void
    {
        $sellerUser = User::factory()->create(['role' => 'seller']);
        $seller = SellerProfile::factory()->create(['user_id' => $sellerUser->id]);
        $token = $sellerUser->createToken('seller_token')->plainTextToken;
        $category = Category::factory()->create();

        $payload = [
            'category_id' => $category->id,
            'name' => 'Oversized Washed Tee',
            'description' => 'Heavyweight cotton washed boxy tee.',
            'price' => 4500,
            'stock' => 50,
            'sku' => 'TEST-TEE-01',
            'variants' => [
                ['size' => 'S', 'color' => 'Faded Black', 'sku' => 'TEST-TEE-01-S', 'stock_quantity' => 20],
                ['size' => 'M', 'color' => 'Faded Black', 'sku' => 'TEST-TEE-01-M', 'stock_quantity' => 30],
            ],
            'images' => [
                'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=800',
            ],
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson(route('api.v1.seller.products.store'), $payload);

        $response->assertStatus(201)
            ->assertJsonPath('product.name', 'Oversized Washed Tee')
            ->assertJsonCount(2, 'product.variants')
            ->assertJsonCount(1, 'product.gallery_images');

        $this->assertDatabaseHas('product_variants', [
            'sku' => 'TEST-TEE-01-S',
            'stock_quantity' => 20,
        ]);
    }

    public function test_seller_can_update_fulfillment_status(): void
    {
        $sellerUser = User::factory()->create(['role' => 'seller']);
        $seller = SellerProfile::factory()->create(['user_id' => $sellerUser->id]);
        $token = $sellerUser->createToken('seller_token')->plainTextToken;

        $order = Order::factory()->create(['status' => 'paid']);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'seller_id' => $seller->id,
            'product_name' => 'Sample Item',
            'unit_price' => 5000,
            'quantity' => 1,
            'total_price' => 5000,
            'fulfillment_status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson(route('api.v1.seller.orders.fulfillment', $item), [
                'status' => 'shipped',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('order_item.fulfillment_status', 'shipped');

        $this->assertDatabaseHas('order_items', [
            'id' => $item->id,
            'fulfillment_status' => 'shipped',
        ]);
    }

    public function test_buyer_cannot_access_seller_portal(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $token = $buyer->createToken('buyer_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson(route('api.v1.seller.dashboard'));

        $response->assertStatus(403);
    }
}
