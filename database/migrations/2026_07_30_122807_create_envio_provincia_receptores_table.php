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
        Schema::create('envio_provincia_receptores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_envio_provincia_detalle')->unique();
            $table->string('nombre', 150);
            $table->string('dni', 8);
            $table->string('telefono', 20)->nullable();
            $table->timestamps();

            $table->foreign('id_envio_provincia_detalle')
                  ->references('idEnvioProvinciaDetalle')
                  ->on('envio_provincia_detalles')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('envio_provincia_receptores');
    }
};
