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
        Schema::create('regla_tarifa_envios', function (Blueprint $table) {
            $table->id();
            $table->string('plataforma'); // RIPLEY, FALABELLA, etc.
            $table->decimal('peso_maximo', 8, 2)->nullable(); // Peso máximo en KG (si es null, es el 'ELSE' o por defecto)
            $table->decimal('monto_fijo', 10, 2); // Costo de la tarifa
            $table->boolean('estado')->default(1);
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regla_tarifa_envios');
    }
};
