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
        Schema::table('inventario_proveedor_skus', function (Blueprint $table) {
            $table->decimal('precio_b2b', 12, 2)->nullable()->after('part_number');
            $table->decimal('precio_promo', 12, 2)->nullable()->after('precio_b2b');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventario_proveedor_skus', function (Blueprint $table) {
            $table->dropColumn(['precio_b2b', 'precio_promo']);
        });
    }
};
