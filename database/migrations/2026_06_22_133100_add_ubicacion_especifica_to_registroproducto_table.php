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
        Schema::table('RegistroProducto', function (Blueprint $table) {
            $table->string('ubicacion_especifica', 255)->nullable()->after('observacion')->comment('Ubicación específica de este ítem (solo si difiere de la ubicación principal del inventario)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('RegistroProducto', function (Blueprint $table) {
            $table->dropColumn('ubicacion_especifica');
        });
    }
};
