<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('DivisionPack', function (Blueprint $table) {
            $table->increments('idDivision');
            $table->integer('idRegistroPack')->comment('Registro del pack que se dividió');
            $table->integer('idUser')->comment('Usuario que realizó la operación');
            $table->enum('tipo', ['DIVISION', 'REUNION'])->default('DIVISION');
            $table->date('fechaDivision');
            $table->string('observacion', 500)->nullable();
            $table->timestamps();

            $table->foreign('idRegistroPack')->references('idRegistro')->on('RegistroProducto')->onDelete('cascade');
            $table->foreign('idUser')->references('idUser')->on('Usuario')->onDelete('cascade');
        });

        Schema::create('DivisionPackDetalle', function (Blueprint $table) {
            $table->increments('idDivisionDetalle');
            $table->unsignedInteger('idDivision');
            $table->integer('idRegistroHijo')->comment('Registro del componente hijo creado');
            $table->integer('idProductoHijo');

            $table->foreign('idDivision')->references('idDivision')->on('DivisionPack')->onDelete('cascade');
            $table->foreign('idRegistroHijo')->references('idRegistro')->on('RegistroProducto')->onDelete('cascade');
            $table->foreign('idProductoHijo')->references('idProducto')->on('Producto')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('DivisionPackDetalle');
        Schema::dropIfExists('DivisionPack');
    }
};
