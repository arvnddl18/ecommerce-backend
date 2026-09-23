<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SellerProductLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_update_their_own_product(): void
    {
        $sellerUser = User::factory()->create(['role' => 'seller']);
        $seller = SellerProfile::create([
            'user_id' => $sellerUser->id,
            'store_name' => 'Studio Noir',
            'slug' => 'studio-noir',
            'verification_status' => 'approved',
        ]);

        $category = Category::create(['name' => 'Knitwear', 'slug' => 'knitwear']);

        $product = Product::create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'name' => 'Raw Silk Crewneck',
            'slug' => 'raw-silk-crewneck-001',
            'price' => 19500,
            'stock' => 15,
            'sku' => 'NOIR-SILK-001',
            'status' => 'active',
            'is_active' => true,
        ]);

        Sanctum::actingAs($sellerUser);

        $response = $this->putJson("/api/v1/seller/products/{$product->id}", [
            'name' => 'Raw Silk Crewneck - Edition II',
            'price' => 22000,
            'stock' => 25,
            'description' => 'Heavy gauge raw textured silk.',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('product.name', 'Raw Silk Crewneck - Edition II')
            ->assertJsonPath('product.price', 22000)
            ->assertJsonPath('product.stock', 25);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Raw Silk Crewneck - Edition II',
            'price' => 22000,
        ]);
    }

    public function test_seller_can_archive_their_product(): void
    {
        $sellerUser = User::factory()->create(['role' => 'seller']);
        $seller = SellerProfile::create([
            'user_id' => $sellerUser->id,
            'store_name' => 'Studio Noir',
            'slug' => 'studio-noir',
            'verification_status' => 'approved',
        ]);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status' => 'active',
            'is_active' => true,
        ]);

        Sanctum::actingAs($sellerUser);

        $response = $this->deleteJson("/api/v1/seller/products/{$product->id}");
        $response->assertStatus(200);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'status' => 'archived',
            'is_active' => false,
        ]);
    }

    public function test_seller_cannot_modify_another_sellers_product(): void
    {
        $seller1 = User::factory()->create(['role' => 'seller']);
        $profile1 = SellerProfile::create([
            'user_id' => $seller1->id,
            'store_name' => 'Store 1',
            'slug' => 'store-1',
            'verification_status' => 'approved',
        ]);

        $seller2 = User::factory()->create(['role' => 'seller']);
        SellerProfile::create([
            'user_id' => $seller2->id,
            'store_name' => 'Store 2',
            'slug' => 'store-2',
            'verification_status' => 'approved',
        ]);

        $product = Product::factory()->create([
            'seller_id' => $profile1->id,
        ]);

        Sanctum::actingAs($seller2);

        $response = $this->putJson("/api/v1/seller/products/{$product->id}", [
            'name' => 'Unauthorized Hijack',
        ]);
        $response->assertStatus(403);

        $deleteResponse = $this->deleteJson("/api/v1/seller/products/{$product->id}");
        $deleteResponse->assertStatus(403);
    }
}
