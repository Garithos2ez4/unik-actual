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
        Schema::create('DetalleProducto', function (Blueprint $table) {
            $table->increments('idDetalleProducto');
            $table->integer('idProducto');
            $table->boolean('mostrarPrecioWeb')->default(true);
            $table->decimal('precio_pase', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_producto');
    }
};
