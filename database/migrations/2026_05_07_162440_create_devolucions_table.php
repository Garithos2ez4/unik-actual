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
        Schema::create('devoluciones', function (Blueprint $table) {
            $table->id('idDevolucion');
            $table->unsignedBigInteger('idEgreso'); // Para saber de qu venta provino
            $table->unsignedBigInteger('idRegistro'); // Para saber qu producto fsico es
            $table->unsignedBigInteger('idUser'); // Quin registr la devolucin
            $table->enum('tipo', ['DEVOLUCION', 'GARANTIA']); // Diferenciamos el flujo
            $table->string('plataforma')->nullable(); // MercadoLibre, Linio, Tienda, etc. (Hecho nullable por si no siempre aplica)
            $table->text('motivo'); // Por qu lo devolvieron
            $table->text('detalleReparacion')->nullable(); // Llenado por el tcnico para GARANTA
            $table->boolean('aptoParaVenta')->default(false); // Flag crucial para revender
            $table->timestamps(); // created_at ser tu fechaDevolucion
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devoluciones');
    }
};
