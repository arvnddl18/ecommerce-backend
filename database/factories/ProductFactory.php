<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => fake()->paragraphs(2, true),
            'price' => fake()->numberBetween(1000, 99900), // In cents ($10.00 - $999.00)
            'stock' => fake()->numberBetween(5, 100),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####-????')),
            'images' => [
                'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800&q=80',
            ],
            'is_active' => true,
        ];
    }

    /**
     * Out of stock state.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }
}
