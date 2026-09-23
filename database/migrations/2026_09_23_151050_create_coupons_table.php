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
        Schema::create('coupons', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique()->index();
            $table->foreignId('seller_id')->nullable()->constrained('seller_profiles')->cascadeOnDelete();
            $table->unsignedInteger('discount_percent')->nullable(); // e.g. 10 for 10%
            $table->unsignedInteger('discount_amount')->nullable(); // in cents, e.g. 1500 for $15.00
            $table->unsignedInteger('min_order_amount')->default(0); // in cents
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('uses_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
