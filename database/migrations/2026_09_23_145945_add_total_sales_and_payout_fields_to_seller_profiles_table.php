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
        Schema::table('seller_profiles', function (Blueprint $table): void {
            $table->unsignedBigInteger('total_sales')->default(0)->after('stripe_account_id');
            $table->string('payout_method')->default('stripe_connect')->after('total_sales');
            $table->decimal('rating', 3, 2)->default(5.00)->after('payout_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table): void {
            $table->dropColumn(['total_sales', 'payout_method', 'rating']);
        });
    }
};
