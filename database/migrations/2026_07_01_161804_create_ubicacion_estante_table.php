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
        Schema::create('UbicacionEstante', function (Blueprint $table) {
            $table->id('idUbicacionExacta');
            $table->integer('idAlmacen');
            $table->string('nombre_rack', 100); // Ej: "Vitrina Alta 1"
            $table->tinyInteger('fila_estante');  // 1, 2, 3 ó 4
            $table->boolean('estado')->default(1);
            $table->timestamps();

            $table->foreign('idAlmacen')->references('idAlmacen')->on('Almacen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('UbicacionEstante');
    }
};
