<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PrecioTienda', function (Blueprint $table) {
            $table->increments('idPrecioTienda');
            $table->integer('idProducto')->unique();
            $table->decimal('precioTienda', 10, 2)->default(0);
            $table->timestamp('updated_at')->nullable();

            $table->foreign('idProducto')
                  ->references('idProducto')
                  ->on('Producto')
                  ->onDelete('cascade');
        });

        Schema::create('HistorialPrecioTienda', function (Blueprint $table) {
            $table->increments('idHistorial');
            $table->integer('idProducto');
            $table->decimal('precioAnterior', 10, 2);
            $table->decimal('precioNuevo', 10, 2);
            $table->integer('idUser')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('idProducto')
                  ->references('idProducto')
                  ->on('Producto')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('HistorialPrecioTienda');
        Schema::dropIfExists('PrecioTienda');
    }
};
