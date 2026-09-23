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
        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('city');
            $table->string('province');
            $table->string('postal_code');
            $table->string('country')->default('US');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('wishlists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->foreignId('seller_id')->nullable()->after('product_id')->constrained('seller_profiles')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->after('seller_id')->constrained('product_variants')->nullOnDelete();
            $table->json('variant_details')->nullable()->after('variant_id');
            $table->string('fulfillment_status')->default('pending')->after('total_price'); // pending, processing, shipped, delivered, cancelled
        });

        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('comment');
            $table->string('status')->default('approved'); // approved, pending, flagged
            $table->timestamps();

            $table->index(['product_id', 'rating']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');

        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropForeign(['seller_id']);
            $table->dropForeign(['variant_id']);
            $table->dropColumn(['seller_id', 'variant_id', 'variant_details', 'fulfillment_status']);
        });

        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('addresses');
    }
};
