<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SellerMediaUploadAndSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_upload_media_image_to_storage(): void
    {
        Storage::fake('public');

        $sellerUser = User::factory()->create(['role' => 'seller']);
        $sellerUser->sellerProfile()->create([
            'store_name' => 'Atelier Noir',
            'slug' => 'atelier-noir',
            'verification_status' => 'approved',
        ]);

        $file = UploadedFile::fake()->image('linen_jacket.jpg', 600, 800);

        $response = $this->actingAs($sellerUser)
            ->postJson(route('api.v1.seller.media.upload'), [
                'image' => $file,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'url', 'path']);

        $path = $response->json('path');
        Storage::disk('public')->assertExists($path);
    }

    public function test_buyer_cannot_upload_seller_media(): void
    {
        Storage::fake('public');

        $buyer = User::factory()->create(['role' => 'buyer']);
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->actingAs($buyer)
            ->postJson(route('api.v1.seller.media.upload'), [
                'image' => $file,
            ]);

        $response->assertStatus(403);
    }

    public function test_product_suggestions_returns_matching_entities(): void
    {
        $category = Category::factory()->create(['name' => 'Outerwear Jackets']);

        $sellerUser = User::factory()->create(['role' => 'seller']);
        $seller = SellerProfile::create([
            'user_id' => $sellerUser->id,
            'store_name' => 'Outerwear Studio',
            'slug' => 'outerwear-studio',
            'verification_status' => 'approved',
        ]);

        $product = Product::factory()->create([
            'name' => 'Outerwear Trench Coat',
            'sku' => 'OTC-001',
            'is_active' => true,
            'category_id' => $category->id,
            'seller_id' => $seller->id,
        ]);

        // Search with query "Outerwear"
        $response = $this->getJson(route('api.v1.products.suggestions', ['query' => 'Outerwear']));

        $response->assertStatus(200)
            ->assertJsonPath('query', 'Outerwear')
            ->assertJsonCount(1, 'products')
            ->assertJsonPath('products.0.name', 'Outerwear Trench Coat')
            ->assertJsonCount(1, 'categories')
            ->assertJsonPath('categories.0.name', 'Outerwear Jackets')
            ->assertJsonCount(1, 'sellers')
            ->assertJsonPath('sellers.0.store_name', 'Outerwear Studio');
    }

    public function test_short_query_returns_empty_suggestions(): void
    {
        $response = $this->getJson(route('api.v1.products.suggestions', ['query' => 'a']));

        $response->assertStatus(200)
            ->assertJsonPath('products', [])
            ->assertJsonPath('categories', [])
            ->assertJsonPath('sellers', []);
    }
}
