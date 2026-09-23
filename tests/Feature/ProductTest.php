<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_endpoint_returns_listing(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->getJson(route('api.v1.categories.index'));

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'description'],
                ],
            ]);
    }

    public function test_products_endpoint_returns_active_products(): void
    {
        Product::factory()->count(4)->create(['is_active' => true]);
        Product::factory()->count(2)->create(['is_active' => false]);

        $response = $this->getJson(route('api.v1.products.index'));

        $response->assertStatus(200)
            ->assertJsonCount(4, 'data');
    }

    public function test_products_can_be_filtered_by_category(): void
    {
        $audioCategory = Category::factory()->create(['slug' => 'audio']);
        $wearCategory = Category::factory()->create(['slug' => 'wearables']);

        Product::factory()->count(2)->create(['category_id' => $audioCategory->id, 'is_active' => true]);
        Product::factory()->count(3)->create(['category_id' => $wearCategory->id, 'is_active' => true]);

        $response = $this->getJson(route('api.v1.products.index', ['category' => 'audio']));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_products_can_be_searched_by_name(): void
    {
        Product::factory()->create(['name' => 'AeroSound Wireless', 'is_active' => true]);
        Product::factory()->create(['name' => 'Viper Gaming Mouse', 'is_active' => true]);

        $response = $this->getJson(route('api.v1.products.index', ['search' => 'AeroSound']));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'AeroSound Wireless');
    }

    public function test_single_product_can_be_retrieved(): void
    {
        $product = Product::factory()->create([
            'name' => 'Chronos Titan Watch',
            'is_active' => true,
        ]);

        $response = $this->getJson(route('api.v1.products.show', $product));

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Chronos Titan Watch')
            ->assertJsonPath('data.sku', $product->sku);
    }

    public function test_inactive_product_returns_not_found(): void
    {
        $product = Product::factory()->create(['is_active' => false]);

        $response = $this->getJson(route('api.v1.products.show', $product));

        $response->assertStatus(404);
    }
}
