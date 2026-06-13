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
        Schema::create('tipo_paquete_envios', function (Blueprint $table) {
            $table->id('idTipoPaquete');
            $table->string('nombre');
            $table->string('icono')->nullable();
            $table->string('clave_api')->nullable()->comment('Clave exacta usada en el JSON de Shalom, ej. cajapaquetexxs');
            $table->unsignedBigInteger('idAgencia')->nullable();
            $table->decimal('largo_defecto', 8, 2)->nullable();
            $table->decimal('ancho_defecto', 8, 2)->nullable();
            $table->decimal('alto_defecto', 8, 2)->nullable();
            $table->decimal('peso_maximo', 8, 2)->nullable();
            $table->boolean('estado')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_paquete_envios');
    }
};
