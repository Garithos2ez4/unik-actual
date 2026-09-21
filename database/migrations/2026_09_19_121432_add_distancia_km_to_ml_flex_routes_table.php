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
        Schema::table('ml_flex_routes', function (Blueprint $table) {
            $table->decimal('distancia_km', 8, 2)->nullable()->after('route_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ml_flex_routes', function (Blueprint $table) {
            $table->dropColumn('distancia_km');
        });
    }
};
