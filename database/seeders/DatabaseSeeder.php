<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'role' => 'buyer',
                'age_verified' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin@apexmarketplace.com'],
            [
                'name' => 'Platform Super Admin',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'age_verified' => true,
            ]
        );

        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
        ]);
    }
}
