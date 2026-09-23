<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Sellers
        $sellerUser1 = User::firstOrCreate(
            ['email' => 'apex@marketplace.test'],
            [
                'name' => 'Apex Threads Lab',
                'password' => bcrypt('password123'),
                'role' => 'seller',
                'age_verified' => true,
            ]
        );
        $seller1 = SellerProfile::firstOrCreate(
            ['user_id' => $sellerUser1->id],
            [
                'store_name' => 'Apex Threads Lab',
                'slug' => 'apex-threads-lab',
                'verification_status' => 'approved',
                'stripe_account_id' => 'acct_apex_demo123',
                'bio' => 'Contemporary oversized streetwear and heavyweight fleece engineered in Los Angeles.',
            ]
        );

        $sellerUser2 = User::firstOrCreate(
            ['email' => 'noir@marketplace.test'],
            [
                'name' => 'Noir Technical Studio',
                'password' => bcrypt('password123'),
                'role' => 'seller',
                'age_verified' => true,
            ]
        );
        $seller2 = SellerProfile::firstOrCreate(
            ['user_id' => $sellerUser2->id],
            [
                'store_name' => 'Noir Technical Studio',
                'slug' => 'noir-technical-studio',
                'verification_status' => 'approved',
                'stripe_account_id' => 'acct_noir_demo456',
                'bio' => 'All-weather technical outerwear, taped waterproof shells, and urban utility wear.',
            ]
        );

        $sellerUser3 = User::firstOrCreate(
            ['email' => 'solace@marketplace.test'],
            [
                'name' => 'Solace Essentials',
                'password' => bcrypt('password123'),
                'role' => 'seller',
                'age_verified' => true,
            ]
        );
        $seller3 = SellerProfile::firstOrCreate(
            ['user_id' => $sellerUser3->id],
            [
                'store_name' => 'Solace Essentials',
                'slug' => 'solace-essentials',
                'verification_status' => 'approved',
                'stripe_account_id' => 'acct_solace_demo789',
                'bio' => 'Ultra-soft organic combed cotton basics and tailored everyday silhouettes.',
            ]
        );

        // Platform Admin user
        User::firstOrCreate(
            ['email' => 'admin@marketplace.test'],
            [
                'name' => 'Platform Administrator',
                'password' => bcrypt('password123'),
                'role' => 'admin',
                'age_verified' => true,
            ]
        );

        // Standard Demo Buyer
        $buyer = User::firstOrCreate(
            ['email' => 'buyer@marketplace.test'],
            [
                'name' => 'Jordan Rivera',
                'password' => bcrypt('password123'),
                'role' => 'buyer',
                'age_verified' => true,
            ]
        );

        $streetwear = Category::where('slug', 'streetwear-hoodies')->first() ?: Category::first();
        $outerwear = Category::where('slug', 'technical-outerwear')->first() ?: Category::first();
        $essentials = Category::where('slug', 'minimalist-essentials')->first() ?: Category::first();
        $athleisure = Category::where('slug', 'athleisure-performance')->first() ?: Category::first();
        $footwear = Category::where('slug', 'footwear-sneakers')->first() ?: Category::first();

        $apparelListings = [
            [
                'seller_id' => $seller1->id,
                'category_id' => $streetwear->id,
                'name' => 'Heavyweight Dropped-Shoulder Boxy Hoodie',
                'description' => '480 GSM organic cotton french terry hoodie with a relaxed boxy cut, seamless double-layer hood, and ribbed side gussets.',
                'price' => 8500, // ₱85.00
                'stock' => 65,
                'sku' => 'APX-HD-01',
                'images' => [
                    'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=800&q=80',
                    'https://images.unsplash.com/photo-1543087903-1ac2ec7aa8c5?w=800&q=80',
                    'https://images.unsplash.com/photo-1509967419530-da38b4704bc6?w=800&q=80',
                ],
                'variants' => [
                    ['size' => 'S', 'color' => 'Onyx Black', 'sku' => 'APX-HD-01-BLK-S', 'stock_quantity' => 12],
                    ['size' => 'M', 'color' => 'Onyx Black', 'sku' => 'APX-HD-01-BLK-M', 'stock_quantity' => 20],
                    ['size' => 'L', 'color' => 'Onyx Black', 'sku' => 'APX-HD-01-BLK-L', 'stock_quantity' => 15],
                    ['size' => 'XL', 'color' => 'Onyx Black', 'sku' => 'APX-HD-01-BLK-XL', 'stock_quantity' => 8],
                    ['size' => 'M', 'color' => 'Chalk White', 'sku' => 'APX-HD-01-WHT-M', 'stock_quantity' => 10],
                ],
            ],
            [
                'seller_id' => $seller2->id,
                'category_id' => $outerwear->id,
                'name' => 'Vanguard 3-Layer Stormproof Shell Jacket',
                'description' => 'High-performance 20,000mm waterproof breathable technical shell with Aquaguard YKK zippers, articulated sleeves, and packable storm hood.',
                'price' => 9500, // ₱95.00
                'stock' => 38,
                'sku' => 'NOIR-SH-02',
                'images' => [
                    'https://images.unsplash.com/photo-1544441893-675973e31985?w=800&q=80',
                    'https://images.unsplash.com/photo-1548883354-7622d03aca27?w=800&q=80',
                ],
                'variants' => [
                    ['size' => 'S', 'color' => 'Matte Black', 'sku' => 'NOIR-SH-02-BLK-S', 'stock_quantity' => 8],
                    ['size' => 'M', 'color' => 'Matte Black', 'sku' => 'NOIR-SH-02-BLK-M', 'stock_quantity' => 15],
                    ['size' => 'L', 'color' => 'Matte Black', 'sku' => 'NOIR-SH-02-BLK-L', 'stock_quantity' => 10],
                    ['size' => 'M', 'color' => 'Sage Olive', 'sku' => 'NOIR-SH-02-OLV-M', 'stock_quantity' => 5],
                ],
            ],
            [
                'seller_id' => $seller3->id,
                'category_id' => $essentials->id,
                'name' => 'Signature 280 GSM Heavy Crewneck Tee',
                'description' => 'Substantial 100% Pima combed cotton tee with reinforced 1-inch bound collar, preshrunk structured drape, and blind-stitched hem.',
                'price' => 6000, // ₱60.00
                'stock' => 120,
                'sku' => 'SOL-TEE-03',
                'images' => [
                    'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=800&q=80',
                    'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=800&q=80',
                ],
                'variants' => [
                    ['size' => 'S', 'color' => 'Chalk White', 'sku' => 'SOL-TEE-03-WHT-S', 'stock_quantity' => 25],
                    ['size' => 'M', 'color' => 'Chalk White', 'sku' => 'SOL-TEE-03-WHT-M', 'stock_quantity' => 40],
                    ['size' => 'L', 'color' => 'Chalk White', 'sku' => 'SOL-TEE-03-WHT-L', 'stock_quantity' => 30],
                    ['size' => 'M', 'color' => 'Charcoal', 'sku' => 'SOL-TEE-03-CHR-M', 'stock_quantity' => 25],
                ],
            ],
            [
                'seller_id' => $seller1->id,
                'category_id' => $athleisure->id,
                'name' => 'AeroFlex Tapered Cargo Track Pants',
                'description' => 'Four-way technical stretch double-weave knit pants featuring zippered bellow utility cargo pockets, articulated knees, and drawcord waistband.',
                'price' => 7500, // ₱75.00
                'stock' => 50,
                'sku' => 'APX-TRK-04',
                'images' => [
                    'https://images.unsplash.com/photo-1552902865-b72c031ac5ea?w=800&q=80',
                    'https://images.unsplash.com/photo-1517445312882-bc9910d016b7?w=800&q=80',
                ],
                'variants' => [
                    ['size' => 'S', 'color' => 'Deep Navy', 'sku' => 'APX-TRK-04-NVY-S', 'stock_quantity' => 10],
                    ['size' => 'M', 'color' => 'Deep Navy', 'sku' => 'APX-TRK-04-NVY-M', 'stock_quantity' => 20],
                    ['size' => 'L', 'color' => 'Deep Navy', 'sku' => 'APX-TRK-04-NVY-L', 'stock_quantity' => 15],
                    ['size' => 'M', 'color' => 'Onyx Black', 'sku' => 'APX-TRK-04-BLK-M', 'stock_quantity' => 5],
                ],
            ],
            [
                'seller_id' => $seller2->id,
                'category_id' => $footwear->id,
                'name' => 'Velocity V1 Low-Top Minimal Sneaker',
                'description' => 'Handcrafted full-grain Italian leather sneakers featuring Margom vulcanized rubber cupsole, waxed laces, and cushioned calfskin lining.',
                'price' => 9000, // ₱90.00
                'stock' => 40,
                'sku' => 'NOIR-SNK-05',
                'images' => [
                    'https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=800&q=80',
                    'https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77?w=800&q=80',
                ],
                'variants' => [
                    ['size' => 'US 9', 'color' => 'Triple White', 'sku' => 'NOIR-SNK-05-WHT-9', 'stock_quantity' => 10],
                    ['size' => 'US 10', 'color' => 'Triple White', 'sku' => 'NOIR-SNK-05-WHT-10', 'stock_quantity' => 15],
                    ['size' => 'US 11', 'color' => 'Triple White', 'sku' => 'NOIR-SNK-05-WHT-11', 'stock_quantity' => 10],
                    ['size' => 'US 10', 'color' => 'Monochrome Black', 'sku' => 'NOIR-SNK-05-BLK-10', 'stock_quantity' => 5],
                ],
            ],
        ];

        foreach ($apparelListings as $item) {
            $product = Product::updateOrCreate(
                ['sku' => $item['sku']],
                [
                    'seller_id' => $item['seller_id'],
                    'category_id' => $item['category_id'],
                    'name' => $item['name'],
                    'slug' => Str::slug($item['name']),
                    'description' => $item['description'],
                    'price' => $item['price'],
                    'stock' => $item['stock'],
                    'images' => $item['images'],
                    'is_active' => true,
                    'status' => 'active',
                ]
            );

            // Populate variants
            foreach ($item['variants'] as $variantData) {
                $product->variants()->updateOrCreate(
                    ['sku' => $variantData['sku']],
                    [
                        'size' => $variantData['size'],
                        'color' => $variantData['color'],
                        'stock_quantity' => $variantData['stock_quantity'],
                    ]
                );
            }

            // Populate gallery images
            foreach ($item['images'] as $idx => $url) {
                $product->galleryImages()->updateOrCreate(
                    ['url' => $url],
                    ['sort_order' => $idx]
                );
            }

            // Seed a verified review
            Review::firstOrCreate(
                ['product_id' => $product->id, 'user_id' => $buyer->id],
                [
                    'rating' => 5,
                    'comment' => 'Outstanding drape and fabric weight. Fits true to size with a high-end streetwear silhouette.',
                    'status' => 'approved',
                ]
            );
        }
    }
}
