<?php

namespace Database\Factories;

use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SellerProfile>
 */
class SellerProfileFactory extends Factory
{
    protected $model = SellerProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $storeName = fake()->company().' Apparel';

        return [
            'user_id' => User::factory()->create(['role' => 'seller']),
            'store_name' => $storeName,
            'slug' => Str::slug($storeName).'-'.fake()->unique()->numberBetween(100, 999),
            'verification_status' => 'approved',
            'stripe_account_id' => 'acct_test_'.Str::random(16),
            'bio' => fake()->paragraph(),
            'logo_url' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&q=80',
        ];
    }

    /**
     * Indicate that the seller profile is pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'pending',
        ]);
    }
}
