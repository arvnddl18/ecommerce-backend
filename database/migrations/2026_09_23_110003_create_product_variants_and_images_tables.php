<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->foreignId('seller_id')->nullable()->after('category_id')->constrained('seller_profiles')->nullOnDelete();
            $table->string('status')->default('active')->index()->after('is_active'); // draft, active, archived
        });

        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('size'); // XS, S, M, L, XL, XXL, etc.
            $table->string('color'); // Onyx, Chalk, Sage, Cobalt, etc.
            $table->string('sku')->unique();
            $table->integer('stock_quantity')->default(0);
            $table->unsignedInteger('price_override')->nullable(); // In cents if different from base price
            $table->timestamps();

            $table->index(['product_id', 'size', 'color']);
        });

        Schema::create('product_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('url');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_variants');

        Schema::table('products', function (Blueprint $table): void {
            $table->dropForeign(['seller_id']);
            $table->dropColumn(['seller_id', 'status']);
        });
    }
};
