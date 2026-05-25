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
        Schema::create('PagoVenta', function (Blueprint $table) {
            $table->integer('idPagoVenta')->autoIncrement();
            $table->integer('idVenta');
            $table->integer('idMetodoPago');
            $table->decimal('monto', 10, 2);
            $table->string('nroOperacion', 50)->nullable();
            $table->timestamp('fechaPago')->useCurrent();

            $table->foreign('idVenta')->references('idVenta')->on('Venta')->onDelete('cascade');
            $table->foreign('idMetodoPago')->references('idMetodoPago')->on('MetodoPago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('PagoVenta');
    }
};
