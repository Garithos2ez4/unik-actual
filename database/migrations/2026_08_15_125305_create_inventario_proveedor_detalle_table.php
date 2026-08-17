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
        Schema::create('InventarioProveedorDetalle', function (Blueprint $table) {
            $table->id('idDetalle');
            $table->string('keyword')->nullable();
            $table->integer('productos_encontrados')->default(0);
            $table->integer('productos_actualizados')->default(0);
            $table->timestamp('fecha_ejecucion')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('InventarioProveedorDetalle');
    }
};
