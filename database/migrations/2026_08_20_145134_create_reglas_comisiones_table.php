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
        Schema::create('reglas_comisiones', function (Blueprint $table) {
            $table->id();
            $table->string('plataforma', 50);
            $table->string('nombre_regla', 100);
            $table->string('tipo_condicion', 50)->default('DEFAULT'); 
            $table->string('valor_condicion', 255)->nullable();
            $table->decimal('porcentaje_comision', 8, 4)->nullable(); 
            $table->decimal('monto_fijo', 8, 2)->nullable();
            $table->integer('prioridad')->default(0); 
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reglas_comisiones');
    }
};
