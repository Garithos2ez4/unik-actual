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
        Schema::create('envio_dimensiones', function (Blueprint $table) {
            $table->id('idDimension');
            $table->unsignedBigInteger('idEnvioProvincia');
            $table->unsignedBigInteger('idTipoPaquete')->nullable();
            $table->decimal('largo_final', 8, 2);
            $table->decimal('ancho_final', 8, 2);
            $table->decimal('alto_final', 8, 2);
            $table->decimal('peso_final', 8, 2);
            $table->decimal('precio_calculado', 8, 2)->nullable();
            $table->timestamps();

            // Asumiendo que la tabla principal es envio_provincias
            $table->foreign('idEnvioProvincia')->references('idEnvioProvincia')->on('envio_provincias')->onDelete('cascade');
            $table->foreign('idTipoPaquete')->references('idTipoPaquete')->on('tipo_paquete_envios')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('envio_dimensiones');
    }
};
