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
            if (!Schema::hasColumn('RegistroProducto', 'es_herramienta')) {
                $table->boolean('es_herramienta')->default(false)->after('numeroSerie')->comment('Marca si esta serie se usa como herramienta de servicio multiple');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('RegistroProducto', function (Blueprint $table) {
            if (Schema::hasColumn('RegistroProducto', 'es_herramienta')) {
                $table->dropColumn('es_herramienta');
            }
        });
    }
};
