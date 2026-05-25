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
        Schema::create('TipoMetodoPago', function (Blueprint $table) {
            $table->integer('idTipoMetodo')->autoIncrement();
            $table->string('nombreTipo', 50);
        });

        Schema::create('MetodoPago', function (Blueprint $table) {
            $table->integer('idMetodoPago')->autoIncrement();
            $table->integer('idTipoMetodo');
            $table->integer('idBanco')->nullable();
            $table->string('nombreMetodo', 50);
            $table->tinyInteger('estado')->default(1);

            $table->foreign('idTipoMetodo')->references('idTipoMetodo')->on('TipoMetodoPago');
            $table->foreign('idBanco')->references('idBanco')->on('Banco');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('MetodoPago');
        Schema::dropIfExists('TipoMetodoPago');
    }
};
