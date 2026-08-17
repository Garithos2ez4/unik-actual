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
        Schema::table('InventarioProveedorDetalle', function (Blueprint $table) {
            $table->json('recomendados_json')->nullable()->after('detalles_json');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('InventarioProveedorDetalle', function (Blueprint $table) {
            $table->dropColumn('recomendados_json');
        });
    }
};
