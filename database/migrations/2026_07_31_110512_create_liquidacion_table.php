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
        Schema::create('Liquidacion', function (Blueprint $table) {
            $table->id('idLiquidacion');
            $table->unsignedBigInteger('idProducto');
            $table->decimal('precio_liquidacion', 10, 2)->nullable();
            $table->timestamps();

            // Depending on the DB type, this foreign key might need exactly the right type.
            // Assuming Producto.idProducto is unsignedBigInteger or similar.
            // If it fails, we can adjust.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Liquidacion');
    }
};
