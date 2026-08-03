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
        Schema::create('alerta_precios', function (Blueprint $table) {
            $table->id();
            $table->string('modelo');
            $table->decimal('mi_precio', 10, 2);
            $table->decimal('precio_competidor', 10, 2);
            $table->string('competidor');
            $table->decimal('diferencia_porcentaje', 5, 2)->comment('Ej. 5.50 para 5.5%');
            $table->string('sugerencia')->comment('SUBIR o BAJAR');
            $table->string('estado')->default('pendiente')->comment('pendiente, procesada');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerta_precios');
    }
};
