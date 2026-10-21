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
            $table->boolean('hay_mas')->default(false)->after('precio_promo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventario_proveedor_skus', function (Blueprint $table) {
            $table->dropColumn('hay_mas');
        });
    }
};
