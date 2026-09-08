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
        Schema::table('ml_orders', function (Blueprint $table) {
            $table->decimal('seller_shipping_cost', 10, 2)->nullable()->after('shipping_id');
            $table->decimal('receiver_shipping_cost', 10, 2)->nullable()->after('seller_shipping_cost');
            $table->decimal('promoted_shipping_amount', 10, 2)->nullable()->after('receiver_shipping_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ml_orders', function (Blueprint $table) {
            $table->dropColumn([
                'seller_shipping_cost',
                'receiver_shipping_cost',
                'promoted_shipping_amount'
            ]);
        });
    }
};
