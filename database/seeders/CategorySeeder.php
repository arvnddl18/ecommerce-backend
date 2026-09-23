<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Streetwear & Hoodies',
                'description' => 'Heavyweight cotton hoodies, oversized dropped-shoulder silhouettes, and graphic apparel.',
            ],
            [
                'name' => 'Technical Outerwear',
                'description' => 'Waterproof shell jackets, insulated trench coats, and breathable commuter layers.',
            ],
            [
                'name' => 'Minimalist Essentials',
                'description' => 'Clean organic cotton tees, tailored trousers, and daily monochrome staples.',
            ],
            [
                'name' => 'Athleisure & Performance',
                'description' => 'Four-way stretch joggers, moisture-wicking tops, and studio activewear.',
            ],
            [
                'name' => 'Footwear & Sneakers',
                'description' => 'Low-top vulcanized sneakers, running trainers, and durable leather boots.',
            ],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['slug' => Str::slug($cat['name'])],
                [
                    'name' => $cat['name'],
                    'description' => $cat['description'],
                ]
            );
        }
    }
}
