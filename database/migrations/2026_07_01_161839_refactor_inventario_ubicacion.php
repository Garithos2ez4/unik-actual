<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Inventario', function (Blueprint $table) {
            // Add new FK column if it doesn't exist (in case of partial migration)
            if (!Schema::hasColumn('Inventario', 'idUbicacionExacta')) {
                $table->unsignedBigInteger('idUbicacionExacta')->nullable()->after('stock');

                $table->foreign('idUbicacionExacta')
                      ->references('idUbicacionExacta')
                      ->on('UbicacionEstante')
                      ->nullOnDelete();
            }

            // Drop the old columns if they exist
            if (Schema::hasColumn('Inventario', 'ubicacion_fisica')) {
                $table->dropColumn('ubicacion_fisica');
            }
            if (Schema::hasColumn('Inventario', 'fila_estante')) {
                $table->dropColumn('fila_estante');
            }
        });
    }

    public function down(): void
    {
        Schema::table('Inventario', function (Blueprint $table) {
            $table->dropForeign(['idUbicacionExacta']);
            $table->dropColumn('idUbicacionExacta');
            $table->unsignedBigInteger('ubicacion_fisica')->nullable();
            $table->string('fila_estante')->nullable();
        });
    }
};
