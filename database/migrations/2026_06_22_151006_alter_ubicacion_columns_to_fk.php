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
            // Eliminar la columna varchar actual
            $table->dropColumn('ubicacion_fisica');
        });
        
        Schema::table('RegistroProducto', function (Blueprint $table) {
            $table->dropColumn('ubicacion_especifica');
        });

        Schema::table('Inventario', function (Blueprint $table) {
            $table->unsignedBigInteger('ubicacion_fisica')->nullable();
        });

        Schema::table('RegistroProducto', function (Blueprint $table) {
            $table->unsignedBigInteger('ubicacion_especifica')->nullable();
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
        Schema::table('RegistroProducto', function (Blueprint $table) {
            $table->dropColumn('ubicacion_especifica');
        });

        Schema::table('Inventario', function (Blueprint $table) {
            $table->string('ubicacion_fisica', 255)->nullable();
        });
        Schema::table('RegistroProducto', function (Blueprint $table) {
            $table->string('ubicacion_especifica', 255)->nullable();
        });
    }
};
