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
            $table->dropColumn('buyer_phone');
        });

        Schema::table('ml_flex_routes', function (Blueprint $table) {
            $table->string('buyer_phone')->nullable()->after('route_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ml_flex_routes', function (Blueprint $table) {
            $table->dropColumn('buyer_phone');
        });

        Schema::table('ml_orders', function (Blueprint $table) {
            $table->string('buyer_phone')->nullable()->after('buyer_nickname');
        });
    }
};
