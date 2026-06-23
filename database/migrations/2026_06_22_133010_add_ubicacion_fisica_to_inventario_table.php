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
        Schema::table('Inventario', function (Blueprint $table) {
            $table->string('ubicacion_fisica', 255)->nullable()->after('stock')->comment('Ubicación predeterminada en este almacén (ej: Pasillo 3, Rack A, Estante 2)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Inventario', function (Blueprint $table) {
            $table->dropColumn('ubicacion_fisica');
        });
    }
};
