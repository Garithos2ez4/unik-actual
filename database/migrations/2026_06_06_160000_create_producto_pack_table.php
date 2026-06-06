<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ProductoPack', function (Blueprint $table) {
            $table->increments('idPack');
            $table->integer('idProductoPack')->comment('ID del producto pack (padre)');
            $table->integer('idProductoHijo')->comment('ID del producto componente (hijo)');
            $table->unsignedTinyInteger('cantidad')->default(1)->comment('Cantidad de este hijo por pack');

            $table->foreign('idProductoPack')->references('idProducto')->on('Producto')->onDelete('cascade');
            $table->foreign('idProductoHijo')->references('idProducto')->on('Producto')->onDelete('cascade');

            $table->unique(['idProductoPack', 'idProductoHijo'], 'uq_pack_hijo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ProductoPack');
    }
};
