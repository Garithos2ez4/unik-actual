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
        Schema::create('plataforma_mappers', function (Blueprint $table) {
            $table->id();
            $table->integer('idPlataforma');
            $table->integer('idCategoria')->nullable();
            $table->integer('idGrupoProducto')->nullable();
            $table->string('tipo_template')->default('express')->comment('express o completo');
            $table->timestamps();

            $table->foreign('idPlataforma')->references('idPlataforma')->on('Plataforma')->onDelete('cascade');
            $table->foreign('idCategoria')->references('idCategoria')->on('CategoriaProducto')->onDelete('cascade');
            $table->foreign('idGrupoProducto')->references('idGrupoProducto')->on('GrupoProducto')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plataforma_mappers');
    }
};
