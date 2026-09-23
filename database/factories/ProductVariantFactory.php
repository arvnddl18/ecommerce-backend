<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'size' => fake()->randomElement(['XS', 'S', 'M', 'L', 'XL', 'XXL']),
            'color' => fake()->randomElement(['Onyx Black', 'Chalk White', 'Sage Green', 'Cobalt Blue', 'Heather Gray']),
            'sku' => strtoupper(fake()->unique()->bothify('VAR-####-????')),
            'stock_quantity' => fake()->numberBetween(5, 50),
            'price_override' => null,
        ];
    }
}
